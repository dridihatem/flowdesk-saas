<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectSource;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Provider;
use App\Support\Database\YearMonthGroup;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @return array{labels: list<string>, invoice_counts: list<int>, invoice_amounts_minor: list<int>, paid_amounts_minor: list<int>}
     */
    public function monthlySeries(Company $company, int $months = 6): array
    {
        $cid = $company->id;
        $monthKeys = $this->monthKeys($months);
        $ym = YearMonthGroup::column('invoices.created_at');
        $rangeStart = Carbon::parse($monthKeys[0])->startOfMonth();

        $rows = Invoice::query()
            ->withoutGlobalScope('tenant')
            ->where('company_id', $cid)
            ->where('created_at', '>=', $rangeStart)
            ->select([
                DB::raw("{$ym} as ym"),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('COALESCE(SUM(amount), 0) as invoice_amount'),
                DB::raw("COALESCE(SUM(CASE WHEN status = '".InvoiceStatus::Paid->value."' THEN amount ELSE 0 END), 0) as paid_amount"),
            ])
            ->groupBy(DB::raw($ym))
            ->get()
            ->keyBy('ym');

        $labels = [];
        $invoiceCounts = [];
        $invoiceAmounts = [];
        $paidAmounts = [];

        foreach ($monthKeys as $ymKey) {
            $labels[] = Carbon::parse($ymKey.'-01')->translatedFormat('M Y');
            $row = $rows->get($ymKey);
            $invoiceCounts[] = (int) ($row->invoice_count ?? 0);
            $invoiceAmounts[] = (int) ($row->invoice_amount ?? 0);
            $paidAmounts[] = (int) ($row->paid_amount ?? 0);
        }

        return [
            'labels' => $labels,
            'invoice_counts' => $invoiceCounts,
            'invoice_amounts_minor' => $invoiceAmounts,
            'paid_amounts_minor' => $paidAmounts,
        ];
    }

    /**
     * Completed payments only (not invoice rows) — for charts & trends.
     *
     * @return array{labels: list<string>, payment_counts: list<int>, payment_amounts_minor: list<int>}
     */
    public function monthlyPaymentSeries(Company $company, int $months = 6): array
    {
        $cid = $company->id;
        $monthKeys = $this->monthKeys($months);
        $ym = YearMonthGroup::column('payments.created_at');
        $rangeStart = Carbon::parse($monthKeys[0])->startOfMonth();

        $rows = Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', PaymentStatus::Completed)
            ->where('created_at', '>=', $rangeStart)
            ->select([
                DB::raw("{$ym} as ym"),
                DB::raw('COUNT(*) as payment_count'),
                DB::raw('COALESCE(SUM(amount), 0) as payment_amount'),
            ])
            ->groupBy(DB::raw($ym))
            ->get()
            ->keyBy('ym');

        $labels = [];
        $paymentCounts = [];
        $paymentAmountsMinor = [];

        foreach ($monthKeys as $ymKey) {
            $labels[] = Carbon::parse($ymKey.'-01')->translatedFormat('M Y');
            $row = $rows->get($ymKey);
            $paymentCounts[] = (int) ($row->payment_count ?? 0);
            $paymentAmountsMinor[] = (int) ($row->payment_amount ?? 0);
        }

        return [
            'labels' => $labels,
            'payment_counts' => $paymentCounts,
            'payment_amounts_minor' => $paymentAmountsMinor,
        ];
    }

    /**
     * Totals of completed payments grouped by gateway / channel (`payments.provider`).
     *
     * @return array{labels: list<string>, keys: list<string>, amounts_minor: list<int>, counts: list<int>}
     */
    public function completedPaymentsByChannel(Company $company): array
    {
        $cid = $company->id;
        $channelExpr = "LOWER(COALESCE(NULLIF(TRIM(provider), ''), 'other'))";

        $rows = Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', PaymentStatus::Completed)
            ->select([
                DB::raw("{$channelExpr} as channel"),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(amount), 0) as total_minor'),
            ])
            ->groupBy(DB::raw($channelExpr))
            ->orderByDesc('total_minor')
            ->get();

        $labels = [];
        $keys = [];
        $amounts = [];
        $counts = [];

        foreach ($rows as $row) {
            $key = (string) $row->channel;
            $keys[] = $key;
            $labels[] = $this->paymentChannelLabel($key);
            $amounts[] = (int) $row->total_minor;
            $counts[] = (int) $row->cnt;
        }

        return [
            'labels' => $labels,
            'keys' => $keys,
            'amounts_minor' => $amounts,
            'counts' => $counts,
        ];
    }

    public function paymentChannelLabel(string $key): string
    {
        return match (strtolower($key)) {
            'stripe' => __('Stripe'),
            'paypal' => __('PayPal'),
            'flouci' => __('Flouci'),
            'advance' => __('Manual / advance'),
            'other' => __('Other'),
            default => $key !== '' ? strtoupper($key) : __('Other'),
        };
    }

    /**
     * @return array{provider_count: int, average_commission_rate: float|null, open_invoice_volume_minor: int}
     */
    public function providerCommissionSummary(Company $company): array
    {
        $cid = $company->id;
        $providers = Provider::query()->withoutGlobalScopes()->where('company_id', $cid)->get();

        $avg = $providers->avg('commission_rate');

        $openMinor = (int) Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $cid)
            ->whereIn('status', [
                InvoiceStatus::Draft->value,
                InvoiceStatus::Sent->value,
                InvoiceStatus::Overdue->value,
            ])
            ->sum('amount');

        return [
            'provider_count' => $providers->count(),
            'average_commission_rate' => $avg !== null ? round((float) $avg, 4) : null,
            'open_invoice_volume_minor' => $openMinor,
        ];
    }

    /**
     * @return array{by_source: array<string, int>, total: int}
     */
    public function projectSourcesReport(Company $company): array
    {
        $cid = $company->id;
        $counts = Project::query()->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->selectRaw('source, count(*) as c')
            ->groupBy('source')
            ->pluck('c', 'source')
            ->map(fn ($n) => (int) $n)
            ->all();

        $bySource = [];
        foreach (ProjectSource::cases() as $case) {
            $bySource[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return [
            'by_source' => $bySource,
            'total' => array_sum($bySource),
        ];
    }

    /**
     * @return list<array{date: string, count: int, amount_minor: int}>
     */
    public function dailyInvoiceTotals(Company $company, Carbon $from, Carbon $to): array
    {
        $cid = $company->id;
        $rows = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $cid)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(amount), 0) as total')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return $rows->map(fn ($r): array => [
            'date' => (string) $r->d,
            'count' => (int) $r->c,
            'amount_minor' => (int) $r->total,
        ])->all();
    }

    /**
     * @return array<string, array{count: int, amount_minor: int}>
     */
    public function revenueByInvoiceStatusInRange(Company $company, Carbon $from, Carbon $to): array
    {
        $cid = $company->id;
        $rows = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $cid)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('status, COUNT(*) as c, COALESCE(SUM(amount), 0) as total')
            ->groupBy('status')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->status->value] = [
                'count' => (int) $r->c,
                'amount_minor' => (int) $r->total,
            ];
        }

        return $out;
    }

    /**
     * @return Collection<int, array{id: string, name: string, email: string|null, projects_count: int, commission_rate: float|null}>
     */
    public function providerStats(Company $company): Collection
    {
        return Provider::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->withCount('projects')
            ->orderByDesc('projects_count')
            ->orderBy('name')
            ->get()
            ->map(fn (Provider $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->email,
                'projects_count' => $p->projects_count,
                'commission_rate' => $p->commission_rate !== null ? (float) $p->commission_rate : null,
            ]);
    }

    /**
     * @return list<string> YYYY-MM keys for the last N months including current.
     */
    private function monthKeys(int $months): array
    {
        $keys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $keys[] = now()->subMonths($i)->format('Y-m');
        }

        return $keys;
    }
}
