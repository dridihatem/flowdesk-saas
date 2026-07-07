<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\Provider;

class ProviderCommissionService
{
    /**
     * Effective commission rate (0–1) for this provider on this project.
     * Workspace-level tiers (by total client count) override when configured and matching;
     * otherwise each provider’s fixed {@see Provider::$commission_rate} applies.
     */
    public function effectiveRateForProject(Provider $provider, Project $project): ?float
    {
        $company = $provider->company;
        if ($company !== null) {
            $company->loadMissing('settings');
            $workspaceTiers = $company->settings?->provider_commission_client_tiers;
            if (is_array($workspaceTiers) && $workspaceTiers !== []) {
                $clientCount = Client::query()->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->count();
                $fromWorkspace = $this->rateForWorkspaceClientTiers($workspaceTiers, $clientCount);
                if ($fromWorkspace !== null) {
                    return $fromWorkspace;
                }
            }
        }

        return $provider->commission_rate !== null
            ? (float) $provider->commission_rate
            : null;
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     */
    public function rateForWorkspaceClientTiers(array $tiers, int $clientCount): ?float
    {
        $normalized = $this->normalizeWorkspaceClientTiers($tiers);
        if ($normalized === []) {
            return null;
        }

        foreach ($normalized as $tier) {
            $from = $tier['from_clients'];
            $to = $tier['to_clients'];
            if ($clientCount < $from) {
                continue;
            }
            if ($to !== null && $clientCount > $to) {
                continue;
            }

            return $tier['rate'];
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     * @return list<array{from_clients: int, to_clients: int|null, rate: float}>
     */
    public function normalizeWorkspaceClientTiers(array $tiers): array
    {
        $out = [];
        foreach ($tiers as $row) {
            if (! isset($row['from_clients'], $row['rate'])) {
                continue;
            }
            $from = max(0, (int) $row['from_clients']);
            $to = isset($row['to_clients']) && $row['to_clients'] !== '' && $row['to_clients'] !== null
                ? (int) $row['to_clients']
                : null;
            if ($to !== null && $to < $from) {
                continue;
            }
            $rate = (float) $row['rate'];
            if ($rate < 0 || $rate > 1) {
                continue;
            }
            $out[] = ['from_clients' => $from, 'to_clients' => $to, 'rate' => $rate];
        }

        usort($out, fn (array $a, array $b) => $a['from_clients'] <=> $b['from_clients']);

        return $out;
    }
}
