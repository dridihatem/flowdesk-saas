<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Proposal;

class CompanyGrowthAiService
{
    /**
     * @return list<array{mode: string, title: string, description: string, icon: string}>
     */
    public function modules(): array
    {
        return [
            [
                'mode' => 'growth_projects',
                'title' => __('Projects growth advisor'),
                'description' => __('AI decisions on pipeline health, stalled projects, and delivery priorities.'),
                'icon' => 'fa-diagram-project',
            ],
            [
                'mode' => 'growth_invoices',
                'title' => __('Invoices growth advisor'),
                'description' => __('AI suggestions for cash collection, reminders, and revenue recovery.'),
                'icon' => 'fa-file-invoice-dollar',
            ],
            [
                'mode' => 'growth_clients',
                'title' => __('Clients growth advisor'),
                'description' => __('AI recommendations to retain, upsell, and re-engage clients.'),
                'icon' => 'fa-users',
            ],
        ];
    }

    public function buildContext(Company $company, string $mode): string
    {
        return match ($mode) {
            'growth_projects' => $this->projectsContext($company),
            'growth_invoices' => $this->invoicesContext($company),
            'growth_clients' => $this->clientsContext($company),
            default => '',
        };
    }

    private function projectsContext(Company $company): string
    {
        $base = Project::query()->withoutGlobalScopes()->where('company_id', $company->id);
        $total = (clone $base)->count();
        $byStatus = (clone $base)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $inProgress = (int) ($byStatus[ProjectStatus::InProgress->value] ?? 0);
        $pending = (int) ($byStatus[ProjectStatus::Pending->value] ?? 0);
        $draft = (int) ($byStatus[ProjectStatus::Draft->value] ?? 0);
        $completed = (int) ($byStatus[ProjectStatus::Completed->value] ?? 0);

        $stale = (clone $base)
            ->whereIn('status', [ProjectStatus::InProgress->value, ProjectStatus::Approved->value])
            ->where('updated_at', '<', now()->subDays(14))
            ->count();

        $recent = (clone $base)
            ->with('client:id,name')
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'title', 'status', 'client_id', 'updated_at']);

        $lines = [
            'Company: '.$company->name,
            'Currency: '.($company->default_currency ?? 'USD'),
            'Projects total: '.$total,
            'In progress: '.$inProgress,
            'Pending approval: '.$pending,
            'Draft: '.$draft,
            'Completed: '.$completed,
            'Potentially stalled (no update 14+ days): '.$stale,
            '',
            'Recent projects:',
        ];

        foreach ($recent as $p) {
            $lines[] = sprintf(
                '- %s | status: %s | client: %s | updated: %s',
                $p->title,
                $this->enumString($p->status),
                $p->client?->name ?? '—',
                $p->updated_at?->toDateString() ?? '—'
            );
        }

        return implode("\n", $lines);
    }

    private function invoicesContext(Company $company): string
    {
        $base = Invoice::query()->withoutGlobalScopes()->where('company_id', $company->id);
        $currency = $company->default_currency ?? 'USD';

        $openStatuses = [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value, InvoiceStatus::Draft->value];
        $openCount = (clone $base)->whereIn('status', $openStatuses)->count();
        $overdueCount = (clone $base)->where('status', InvoiceStatus::Overdue->value)->count();
        $paid30d = (clone $base)
            ->where('status', InvoiceStatus::Paid->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('amount');
        $openAmount = (clone $base)
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
            ->sum('amount');

        $overdue = (clone $base)
            ->with('client:id,name')
            ->where('status', InvoiceStatus::Overdue->value)
            ->orderByDesc('amount')
            ->limit(8)
            ->get(['id', 'number', 'amount', 'due_date', 'client_id', 'status']);

        $lines = [
            'Company: '.$company->name,
            'Currency: '.$currency,
            'Open invoices: '.$openCount,
            'Overdue invoices: '.$overdueCount,
            'Open amount (minor units): '.$openAmount,
            'Paid revenue last 30 days (minor units): '.$paid30d,
            '',
            'Top overdue invoices:',
        ];

        foreach ($overdue as $inv) {
            $lines[] = sprintf(
                '- %s | amount: %s | due: %s | client: %s',
                $inv->number ?? $inv->id,
                $inv->amount,
                $inv->due_date?->toDateString() ?? '—',
                $inv->client?->name ?? '—'
            );
        }

        return implode("\n", $lines);
    }

    private function clientsContext(Company $company): string
    {
        $base = Client::query()->withoutGlobalScopes()->where('company_id', $company->id);
        $total = (clone $base)->count();

        $withProjects = (clone $base)->whereHas('projects')->count();
        $withoutRecentProject = (clone $base)
            ->whereDoesntHave('projects', fn ($q) => $q->where('updated_at', '>=', now()->subDays(90)))
            ->count();

        $openProposals = Proposal::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [ProposalStatus::Sent->value, ProposalStatus::Draft->value])
            ->count();

        $topClients = (clone $base)
            ->withCount('projects')
            ->orderByDesc('projects_count')
            ->limit(8)
            ->get(['id', 'name', 'email']);

        $lines = [
            'Company: '.$company->name,
            'Total clients: '.$total,
            'Clients with at least one project: '.$withProjects,
            'Clients without project activity in 90 days: '.$withoutRecentProject,
            'Open proposals (draft/sent): '.$openProposals,
            '',
            'Top clients by project count:',
        ];

        foreach ($topClients as $client) {
            $lines[] = sprintf(
                '- %s | email: %s | projects: %d',
                $client->name,
                $client->email ?? '—',
                $client->projects_count
            );
        }

        return implode("\n", $lines);
    }

    private function enumString(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        return (string) ($value ?? '—');
    }
}
