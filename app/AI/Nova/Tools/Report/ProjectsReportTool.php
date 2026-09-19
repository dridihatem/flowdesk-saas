<?php

namespace App\AI\Nova\Tools\Report;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;

class ProjectsReportTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'reports.projects'; }
    public function description(): string { return 'Project status overview.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['filter' => ['type' => 'string']]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {

        $base = \App\Models\Project::query()->withoutGlobalScopes()->where('company_id', $context->companyId);
        return [
            'total' => (clone $base)->count(),
            'in_progress' => (clone $base)->where('status', \App\Enums\ProjectStatus::InProgress)->count(),
            'pending' => (clone $base)->where('status', \App\Enums\ProjectStatus::Pending)->count(),
            'recent' => (clone $base)->orderByDesc('updated_at')->limit(8)->get(['id', 'name', 'status'])->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status?->value ?? $p->status,
            ])->all(),
        ];

    }
}
