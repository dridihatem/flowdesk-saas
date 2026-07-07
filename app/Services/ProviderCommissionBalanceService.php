<?php

namespace App\Services;

use App\Enums\NegotiationStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProviderRemittanceStatus;
use App\Models\Company;
use App\Models\Negotiation;
use App\Models\Project;
use App\Models\Provider;
use App\Models\ProviderRemittanceRequest;
use Illuminate\Support\Collection;

class ProviderCommissionBalanceService
{
    /**
     * @return array{
     *     commission_total_minor: int,
     *     remitted_minor: int,
     *     pending_remittance_minor: int,
     *     balance_due_minor: int,
     *     currency: string,
     * }
     */
    public function summary(Provider $provider): array
    {
        $provider->loadMissing('company');

        $commissionTotal = (int) Negotiation::query()
            ->withoutGlobalScopes()
            ->where('company_id', $provider->company_id)
            ->where('status', NegotiationStatus::Accepted)
            ->whereNotNull('commission_amount_minor')
            ->whereHas('proposal', fn ($query) => $query->where('provider_id', $provider->id))
            ->sum('commission_amount_minor');

        $remitted = (int) ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('provider_id', $provider->id)
            ->where('status', ProviderRemittanceStatus::Approved)
            ->sum('amount_minor');

        $pendingRemittance = (int) ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('provider_id', $provider->id)
            ->where('status', ProviderRemittanceStatus::Pending)
            ->sum('amount_minor');

        $balanceDue = max(0, $commissionTotal - $remitted - $pendingRemittance);

        return [
            'commission_total_minor' => $commissionTotal,
            'remitted_minor' => $remitted,
            'pending_remittance_minor' => $pendingRemittance,
            'balance_due_minor' => $balanceDue,
            'currency' => flowdesk_normalize_currency_code($provider->company?->default_currency),
        ];
    }

    /**
     * @return Collection<int, Negotiation>
     */
    public function recentCommissions(Provider $provider, int $limit = 8): Collection
    {
        return Negotiation::query()
            ->withoutGlobalScopes()
            ->where('company_id', $provider->company_id)
            ->where('status', NegotiationStatus::Accepted)
            ->whereNotNull('commission_amount_minor')
            ->whereHas('proposal', fn ($query) => $query->where('provider_id', $provider->id))
            ->with(['proposal.project'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{
     *     commission_total_minor: int,
     *     remitted_minor: int,
     *     pending_remittance_minor: int,
     *     balance_due_minor: int,
     *     currency: string,
     *     provider_count: int,
     * }
     */
    public function companySummary(Company $company): array
    {
        $currency = flowdesk_normalize_currency_code($company->default_currency);
        $cid = $company->id;

        $commissionTotal = (int) Negotiation::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', NegotiationStatus::Accepted)
            ->whereNotNull('commission_amount_minor')
            ->whereHas('proposal', fn ($query) => $query->where('company_id', $cid))
            ->sum('commission_amount_minor');

        $remitted = (int) ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', ProviderRemittanceStatus::Approved)
            ->sum('amount_minor');

        $pendingRemittance = (int) ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', ProviderRemittanceStatus::Pending)
            ->sum('amount_minor');

        $providerCount = Provider::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->count();

        return [
            'commission_total_minor' => $commissionTotal,
            'remitted_minor' => $remitted,
            'pending_remittance_minor' => $pendingRemittance,
            'balance_due_minor' => max(0, $commissionTotal - $remitted - $pendingRemittance),
            'currency' => $currency,
            'provider_count' => $providerCount,
        ];
    }

    /**
     * @return Collection<int, ProviderRemittanceRequest>
     */
    public function pendingRemittancesForCompany(Company $company, int $limit = 10): Collection
    {
        return ProviderRemittanceRequest::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', ProviderRemittanceStatus::Pending)
            ->with('provider')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Accepted commissions grouped by completed project.
     *
     * @return list<array{project: Project, commission_minor: int, deal_minor: int}>
     */
    public function commissionsByCompletedProject(Provider $provider): array
    {
        $negotiations = Negotiation::query()
            ->withoutGlobalScopes()
            ->where('company_id', $provider->company_id)
            ->where('status', NegotiationStatus::Accepted)
            ->whereNotNull('commission_amount_minor')
            ->where('commission_amount_minor', '>', 0)
            ->whereHas('proposal', fn ($query) => $query
                ->where('provider_id', $provider->id)
                ->whereHas('project', fn ($projectQuery) => $projectQuery->where('status', ProjectStatus::Completed->value))
            )
            ->with(['proposal.project'])
            ->get();

        $byProject = [];

        foreach ($negotiations as $negotiation) {
            $project = $negotiation->proposal?->project;
            if (! $project) {
                continue;
            }

            $projectId = (string) $project->id;

            if (! isset($byProject[$projectId])) {
                $byProject[$projectId] = [
                    'project' => $project,
                    'commission_minor' => 0,
                    'deal_minor' => 0,
                ];
            }

            $byProject[$projectId]['commission_minor'] += (int) $negotiation->commission_amount_minor;
            $byProject[$projectId]['deal_minor'] += (int) $negotiation->amount;
        }

        usort($byProject, fn (array $a, array $b): int => strcmp(
            (string) ($b['project']->updated_at ?? ''),
            (string) ($a['project']->updated_at ?? '')
        ));

        return array_values($byProject);
    }
}
