<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Form as LeadForm;
use App\Models\WidgetEvent;
use Carbon\Carbon;

class MarketingInsightService
{
    /**
     * @return array{labels: list<string>, views: list<int>, submits: list<int>}
     */
    public function widgetTrafficDaily(Company $company, int $days = 30): array
    {
        $cid = $company->id;
        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $viewRows = WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('event', 'view')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $submitRows = WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('event', 'submit')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $labels = [];
        $views = [];
        $submits = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $labels[] = $d->translatedFormat('M j');
            $views[] = (int) ($viewRows[$key] ?? 0);
            $submits[] = (int) ($submitRows[$key] ?? 0);
        }

        return compact('labels', 'views', 'submits');
    }

    /**
     * @return list<array{form_id: string, name: string, views: int, submits: int, rate: float|null}>
     */
    public function widgetTrafficByForm(Company $company, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $cid = $company->id;

        $stats = WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->whereIn('event', ['view', 'submit'])
            ->where('created_at', '>=', $since)
            ->whereNotNull('form_id')
            ->selectRaw('form_id, event, COUNT(*) as c')
            ->groupBy('form_id', 'event')
            ->get();

        $byForm = [];
        foreach ($stats as $row) {
            $byForm[(string) $row->form_id][$row->event] = (int) $row->c;
        }

        return LeadForm::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->orderBy('name')
            ->get()
            ->map(function (LeadForm $form) use ($byForm) {
                $formStats = $byForm[(string) $form->id] ?? [];
                $views = (int) ($formStats['view'] ?? 0);
                $submits = (int) ($formStats['submit'] ?? 0);
                $rate = $views > 0 ? round($submits / $views * 100, 2) : null;

                return [
                    'form_id' => $form->id,
                    'name' => $form->name,
                    'views' => $views,
                    'submits' => $submits,
                    'rate' => $rate,
                ];
            })
            ->all();
    }

    /**
     * @return array{views: int, submits: int, rate: float|null}
     */
    public function widgetTrafficTotals(Company $company, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $cid = $company->id;

        $views = (int) WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('event', 'view')
            ->where('created_at', '>=', $since)
            ->count();
        $submits = (int) WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('event', 'submit')
            ->where('created_at', '>=', $since)
            ->count();
        $rate = $views > 0 ? round($submits / $views * 100, 2) : null;

        return compact('views', 'submits', 'rate');
    }

    /**
     * Daily counts for sitewide page views (marketing tracker script), same length as widgetTrafficDaily labels.
     *
     * @return list<int>
     */
    public function sitePageviewDaily(Company $company, int $days = 30): array
    {
        $cid = $company->id;
        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $rows = WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('event', 'pageview')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $counts = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $counts[] = (int) ($rows[$key] ?? 0);
        }

        return $counts;
    }

    public function sitePageviewTotals(Company $company, int $days = 30): int
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        return (int) WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('event', 'pageview')
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * @return list<array{path: string, count: int, title: ?string}>
     */
    public function sitePageTopPaths(Company $company, int $days = 30, int $limit = 12): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        $rows = WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('event', 'pageview')
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->get(['context']);

        $agg = [];
        foreach ($rows as $row) {
            $ctx = is_array($row->context) ? $row->context : [];
            $path = (string) ($ctx['path'] ?? '/');
            if ($path === '') {
                $path = '/';
            }
            if (! isset($agg[$path])) {
                $agg[$path] = ['path' => $path, 'count' => 0, 'title' => isset($ctx['title']) ? (string) $ctx['title'] : null];
            }
            $agg[$path]['count']++;
            if (($agg[$path]['title'] === null || $agg[$path]['title'] === '') && ! empty($ctx['title'])) {
                $agg[$path]['title'] = (string) $ctx['title'];
            }
        }

        usort($agg, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice(array_values($agg), 0, $limit);
    }

    /**
     * Aggregate embed form views/submits that include page path in `context` (from the widget script).
     *
     * @return list<array{path: string, views: int, submits: int, title: ?string}>
     */
    public function widgetLeadTopPaths(Company $company, int $days = 30, int $limit = 12): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        $rows = WidgetEvent::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('event', ['view', 'submit'])
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->get(['event', 'context']);

        $agg = [];
        foreach ($rows as $row) {
            $ctx = is_array($row->context) ? $row->context : [];
            $path = (string) ($ctx['path'] ?? '');
            if ($path === '') {
                continue;
            }
            if (! isset($agg[$path])) {
                $agg[$path] = ['path' => $path, 'views' => 0, 'submits' => 0, 'title' => null];
            }
            if ($row->event === 'view') {
                $agg[$path]['views']++;
            } elseif ($row->event === 'submit') {
                $agg[$path]['submits']++;
            }
            if (($agg[$path]['title'] === null || $agg[$path]['title'] === '') && ! empty($ctx['title'])) {
                $agg[$path]['title'] = (string) $ctx['title'];
            }
        }

        usort($agg, fn (array $a, array $b): int => (($b['views'] + $b['submits']) <=> ($a['views'] + $a['submits'])));

        return array_slice(array_values($agg), 0, $limit);
    }
}
