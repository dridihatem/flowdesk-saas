<?php

namespace App\Http\Controllers;

use App\Services\CompanyThemeService;
use App\Services\MarketingInsightService;
use App\Services\MarketingSeoHintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarketingHubController extends Controller
{
    public function index(Request $request, MarketingInsightService $insights, MarketingSeoHintService $seoHints): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $days = 30;
        $series = $insights->widgetTrafficDaily($company, $days);
        $byForm = $insights->widgetTrafficByForm($company, $days);
        $totals = $insights->widgetTrafficTotals($company, $days);
        $pageviewSeries = $insights->sitePageviewDaily($company, $days);
        $sitePageviews = $insights->sitePageviewTotals($company, $days);
        $topPaths = $insights->sitePageTopPaths($company, $days);
        $widgetTopPaths = $insights->widgetLeadTopPaths($company, $days);

        $themeService = app(CompanyThemeService::class);
        $settings = $themeService->ensureSettings($company);
        $marketing = is_array($settings->marketing) ? $settings->marketing : [];

        $hintList = $seoHints->build(
            $marketing,
            $sitePageviews,
            (int) $totals['views'],
            (int) $totals['submits'],
            $topPaths,
        );

        return view('workspace.marketing-hub', compact(
            'series',
            'byForm',
            'totals',
            'marketing',
            'days',
            'pageviewSeries',
            'sitePageviews',
            'topPaths',
            'widgetTopPaths',
            'hintList',
        ));
    }

    public function update(Request $request, CompanyThemeService $themeService): RedirectResponse
    {
        $user = $request->user();
        abort_if(! $user->company, 403);
        abort_unless($user->hasRole('company_admin'), 403);

        $data = $request->validate([
            'website_url' => ['nullable', 'string', 'max:2048'],
            'google_analytics_measurement_id' => ['nullable', 'string', 'max:64'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:32'],
            'meta_pixel_id' => ['nullable', 'string', 'max:32'],
        ]);

        $normalize = static function (?string $v): ?string {
            if ($v === null) {
                return null;
            }
            $t = trim($v);

            return $t === '' ? null : $t;
        };

        $ga = $normalize($data['google_analytics_measurement_id'] ?? null);
        if ($ga !== null && ! preg_match('/^G-[A-Z0-9]+$/i', $ga)) {
            throw ValidationException::withMessages([
                'google_analytics_measurement_id' => [__('Use a valid GA4 Measurement ID (e.g. G-XXXXXXXXXX).')],
            ]);
        }

        $gtm = $normalize($data['google_tag_manager_id'] ?? null);
        if ($gtm !== null && ! preg_match('/^GTM-[A-Z0-9]+$/i', $gtm)) {
            throw ValidationException::withMessages([
                'google_tag_manager_id' => [__('Use a valid GTM container ID (e.g. GTM-XXXXXXX).')],
            ]);
        }

        $pixel = $normalize($data['meta_pixel_id'] ?? null);
        if ($pixel !== null && ! preg_match('/^[0-9]{8,20}$/', $pixel)) {
            throw ValidationException::withMessages([
                'meta_pixel_id' => [__('Use a numeric Meta Pixel ID.')],
            ]);
        }

        $url = $normalize($data['website_url'] ?? null);
        if ($url !== null && filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'website_url' => [__('Enter a valid URL including https://')],
            ]);
        }

        $settings = $themeService->ensureSettings($user->company);
        $current = is_array($settings->marketing) ? $settings->marketing : [];

        $settings->marketing = array_merge($current, [
            'website_url' => $url,
            'google_analytics_measurement_id' => $ga !== null ? strtoupper($ga) : null,
            'google_tag_manager_id' => $gtm !== null ? strtoupper($gtm) : null,
            'meta_pixel_id' => $pixel,
        ]);
        $settings->save();

        return redirect()->route('marketing.hub')->with('status', __('Saved.'));
    }
}
