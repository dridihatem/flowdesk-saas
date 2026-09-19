<?php

namespace App\AI\Nova\Tools\Report;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;

class RevenueReportTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'reports.revenue'; }
    public function description(): string { return 'Company revenue summary for the current period.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['filter' => ['type' => 'string']]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {

        $metrics = app(\App\Services\DashboardMetricsService::class)->forCompany($context->company);
        $payments = \App\Models\Payment::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->where('status', \App\Enums\PaymentStatus::Completed)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
        return [
            'period' => 'month',
            'currency' => $context->currency,
            'revenue_minor' => (int) $payments,
            'revenue_formatted' => flowdesk_format_minor((int) $payments, $context->currency).' '.$context->currency,
            'open_invoices' => (int) ($metrics['open_invoices_count'] ?? 0),
        ];

    }
}
