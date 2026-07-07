<?php

namespace App\Http\Controllers;

use App\Services\AiCreditUsageService;
use App\Services\AnalyticsService;
use App\Services\DashboardMetricsService;
use App\Services\NovaAssistantService;
use App\Services\PlanLimitService;
use App\Services\ProviderCommissionBalanceService;
use App\Services\WorkspaceCustomizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        WorkspaceCustomizationService $workspace,
        DashboardMetricsService $metrics,
        AnalyticsService $analytics,
        PlanLimitService $planLimits,
        ProviderCommissionBalanceService $providerCommissions,
    ): View|RedirectResponse {
        if ($request->user()->hasRole('client')) {
            return redirect()->route('portal.dashboard');
        }

        $company = $request->user()->company;
        abort_if(! $company, 403);

        $series = $analytics->monthlyPaymentSeries($company);
        $paymentsByChannel = $analytics->completedPaymentsByChannel($company);

        $metricPayload = $metrics->forCompany($company);
        $metricPayload['dashboard_projects'] = $metrics->dashboardProjects($company);

        if ($planLimits->isFeatureEnabled($company, 'providers')) {
            $providerSummary = $providerCommissions->companySummary($company);
            $providerSummary['pending_requests'] = $providerCommissions->pendingRemittancesForCompany($company, 8);
            $metricPayload['provider_commissions'] = $providerSummary;
        }

        $novaPayload = null;
        if ($planLimits->isFeatureEnabled($company, 'ai_credits')) {
            $novaService = app(NovaAssistantService::class);
            $novaPayload = [
                'assistant_name' => $novaService->assistantName($company),
                'summary' => $novaService->summaryMetrics($company),
                'assistant_url' => route('assistant.index'),
                'chat_url' => route('assistant.chat'),
                'credit_cost' => app(AiCreditUsageService::class)->creditsForTask(
                    AiCreditUsageService::TASK_ASSISTANT,
                    'nova_chat'
                ),
            ];
        }

        return view('dashboard', [
            'widgets' => $workspace->resolvedWidgets($company),
            'metrics' => $metricPayload,
            'nova' => $novaPayload,
            'dashboardChart' => [
                'labels' => $series['labels'],
                'counts' => $series['payment_counts'],
                'paidMinor' => $series['payment_amounts_minor'],
                'paymentsByChannel' => $paymentsByChannel,
                'minorScale' => flowdesk_currency_minor_scale($company->default_currency),
            ],
        ]);
    }
}
