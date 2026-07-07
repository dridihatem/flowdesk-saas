<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __invoke(
        Request $request,
        AnalyticsService $analytics,
        DashboardMetricsService $metrics,
    ): View {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $report = $request->query('report', 'overview');
        if (! in_array($report, ['overview', 'daterange', 'providers', 'revenue'], true)) {
            $report = 'overview';
        }

        $from = $request->date('from') ?? Carbon::now()->subMonth()->startOfMonth();
        $to = $request->date('to') ?? Carbon::now()->endOfDay();

        $series = $analytics->monthlyPaymentSeries($company);
        $paymentsByChannel = $analytics->completedPaymentsByChannel($company);
        $commission = $analytics->providerCommissionSummary($company);
        $projectSources = $analytics->projectSourcesReport($company);
        $kpis = $metrics->forCompany($company);

        $dailyTotals = [];
        $revenueByStatus = [];
        $providerRows = Collection::empty();

        if (in_array($report, ['daterange', 'revenue'], true)) {
            $dailyTotals = $analytics->dailyInvoiceTotals($company, $from, $to);
        }

        if ($report === 'revenue') {
            $revenueByStatus = $analytics->revenueByInvoiceStatusInRange($company, $from, $to);
        }

        if ($report === 'providers') {
            $providerRows = $analytics->providerStats($company);
        }

        return view('analytics.index', compact(
            'series',
            'paymentsByChannel',
            'commission',
            'projectSources',
            'kpis',
            'report',
            'from',
            'to',
            'dailyTotals',
            'revenueByStatus',
            'providerRows',
        ));
    }
}
