<?php

namespace App\AI\Nova\Tools\Report;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;

class WorkloadReportTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'reports.workload'; }
    public function description(): string { return 'Team workload analysis.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['filter' => ['type' => 'string']]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {

        return app(\App\AI\Nova\Assignment\WorkloadAnalyzer::class)->companySnapshot($context);

    }
}
