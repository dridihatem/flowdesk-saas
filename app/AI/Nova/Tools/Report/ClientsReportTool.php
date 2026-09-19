<?php

namespace App\AI\Nova\Tools\Report;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;

class ClientsReportTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'reports.clients'; }
    public function description(): string { return 'Client activity and inactivity signals.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['filter' => ['type' => 'string']]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {

        $clients = \App\Models\Client::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)->count();
        $inactiveDays = 90;
        $inactive = \App\Models\Client::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->where(function ($q) use ($inactiveDays): void {
                $q->whereNull('updated_at')->orWhere('updated_at', '<', now()->subDays($inactiveDays));
            })->count();
        return ['clients_total' => $clients, 'inactive_approx' => $inactive, 'inactive_threshold_days' => $inactiveDays];

    }
}
