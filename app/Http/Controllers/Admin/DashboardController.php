<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Database\YearMonthGroup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $months = collect(range(0, 11))
            ->map(fn (int $i) => Carbon::now()->startOfMonth()->subMonths(11 - $i))
            ->values();

        $labels = $months->map(fn (Carbon $d) => $d->format('Y-m'))->all();

        $ym = YearMonthGroup::column('payments.created_at');

        $paidByMonth = Payment::query()
            ->withoutGlobalScopes()
            ->select([
                DB::raw("{$ym} as ym"),
                DB::raw('COUNT(*) as cnt'),
                DB::raw('SUM(payments.amount) as total_minor'),
            ])
            ->where('payments.status', PaymentStatus::Completed)
            ->where('payments.created_at', '>=', $months->first()->copy()->startOfMonth())
            ->groupBy(DB::raw($ym))
            ->pluck('total_minor', 'ym')
            ->all();

        $paidCountByMonth = Payment::query()
            ->withoutGlobalScopes()
            ->select([
                DB::raw("{$ym} as ym"),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->where('payments.status', PaymentStatus::Completed)
            ->where('payments.created_at', '>=', $months->first()->copy()->startOfMonth())
            ->groupBy(DB::raw($ym))
            ->pluck('cnt', 'ym')
            ->all();

        $seriesPaidMinor = array_map(fn (string $ym) => (int) ($paidByMonth[$ym] ?? 0), $labels);
        $seriesPaidCount = array_map(fn (string $ym) => (int) ($paidCountByMonth[$ym] ?? 0), $labels);

        $ymCompany = YearMonthGroup::column('companies.created_at');

        $companiesCreated = Company::query()
            ->select([
                DB::raw("{$ymCompany} as ym"),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->where('companies.created_at', '>=', $months->first()->copy()->startOfMonth())
            ->groupBy(DB::raw($ymCompany))
            ->pluck('cnt', 'ym')
            ->all();

        $seriesCompaniesCreated = array_map(fn (string $ym) => (int) ($companiesCreated[$ym] ?? 0), $labels);

        $revenueByPlan = Payment::query()
            ->withoutGlobalScopes()
            ->select([
                'plans.id as plan_id',
                'plans.name as plan_name',
                'plans.slug as plan_slug',
                DB::raw('SUM(payments.amount) as total_minor'),
                DB::raw('COUNT(*) as payments_count'),
            ])
            ->join('companies', 'companies.id', '=', 'payments.company_id')
            ->leftJoin('plans', 'plans.id', '=', 'companies.plan_id')
            ->where('payments.status', PaymentStatus::Completed)
            ->groupBy('plans.id', 'plans.name', 'plans.slug')
            ->orderByDesc('total_minor')
            ->get();

        $subsByPlan = Subscription::query()
            ->withoutGlobalScopes()
            ->select([
                'plans.id as plan_id',
                'plans.name as plan_name',
                'plans.slug as plan_slug',
                DB::raw('COUNT(*) as subs_count'),
            ])
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.status', 'active')
            ->groupBy('plans.id', 'plans.name', 'plans.slug')
            ->orderByDesc('subs_count')
            ->get()
            ->keyBy('plan_id');

        $plans = Plan::query()->orderBy('name')->get();
        $companiesByPlan = Company::query()
            ->selectRaw('plan_id, COUNT(*) as cnt')
            ->groupBy('plan_id')
            ->pluck('cnt', 'plan_id');

        return view('admin.dashboard', [
            'companiesCount' => Company::query()->count(),
            'plansCount' => Plan::query()->count(),
            'activeSubscriptions' => Subscription::query()->withoutGlobalScopes()->where('status', 'active')->count(),
            'paymentsCount' => Payment::query()->withoutGlobalScopes()->count(),
            'revenueByPlan' => $revenueByPlan,
            'subsByPlan' => $subsByPlan,
            'plans' => $plans,
            'companiesByPlan' => $companiesByPlan,
            'reportsChart' => [
                'labels' => $labels,
                'counts' => $seriesPaidCount,
                'paidMinor' => $seriesPaidMinor,
                'companiesCreated' => $seriesCompaniesCreated,
                'minorScale' => 100,
            ],
        ]);
    }
}
