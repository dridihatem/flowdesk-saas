<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Carbon\Carbon;

class ReportAiService
{
    public function buildCounselContext(Company $company, Carbon $from, Carbon $to): string
    {
        $metrics = app(DashboardMetricsService::class)->forCompany($company);
        $analytics = app(AnalyticsService::class);
        $commission = $analytics->providerCommissionSummary($company);
        $projectSources = $analytics->projectSourcesReport($company);

        $defaultCurrency = strtoupper((string) ($company->default_currency ?? 'USD'));

        $invoiceTotalsByCurrency = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('upper(currency) as currency, COALESCE(SUM(amount), 0) as total_minor')
            ->groupBy('currency')
            ->get()
            ->map(fn ($row): string => strtoupper((string) $row->currency).': '.flowdesk_format_minor((int) $row->total_minor, (string) $row->currency))
            ->implode(', ');

        $invoicesInRange = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $overdueInRange = Invoice::query()->withoutGlobalScope('tenant')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', InvoiceStatus::Overdue->value)
            ->count();

        $projectsInRange = Project::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $paymentsTotalMinor = (int) Payment::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $staleProjects = Project::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [ProjectStatus::InProgress->value, ProjectStatus::Approved->value])
            ->where('updated_at', '<', now()->subDays(14))
            ->count();

        $lines = [
            'Company: '.$company->name,
            'Report period: '.$from->toDateString().' to '.$to->toDateString(),
            'Currency: '.($company->default_currency ?? 'USD'),
            '',
            'Workspace totals (all time):',
            'Clients: '.($metrics['clients_count'] ?? 0),
            'Projects: '.($metrics['projects_count'] ?? 0),
            'Open invoices: '.($metrics['open_invoices_count'] ?? 0),
            'Paid invoices: '.($metrics['paid_invoices_count'] ?? 0),
            '',
            'Selected period:',
            'Invoices created: '.$invoicesInRange,
            'Invoice amount total: '.($invoiceTotalsByCurrency !== '' ? $invoiceTotalsByCurrency : '0'),
            'Overdue invoices in period: '.$overdueInRange,
            'Projects created: '.$projectsInRange,
            'Completed payments total: '.flowdesk_format_minor($paymentsTotalMinor, $defaultCurrency).' '.$defaultCurrency,
            'Potentially stalled projects (14+ days): '.$staleProjects,
            '',
            'Projects by source (all time):',
        ];

        foreach ($projectSources['by_source'] ?? [] as $source => $count) {
            $lines[] = "- {$source}: {$count}";
        }

        $lines[] = '';
        $lines[] = 'Commission context:';
        $lines[] = 'Providers: '.($commission['provider_count'] ?? 0);
        $lines[] = 'Open invoice volume: '.flowdesk_format_minor(
            (int) ($commission['open_invoice_volume_minor'] ?? 0),
            $defaultCurrency
        ).' '.$defaultCurrency;

        return implode("\n", $lines);
    }
}
