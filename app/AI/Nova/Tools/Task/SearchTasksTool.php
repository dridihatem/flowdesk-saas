<?php

namespace App\AI\Nova\Tools\Task;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectTask;

class SearchTasksTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'tasks.search'; }
    public function description(): string { return 'Search tasks in the current company.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'query' => ['type' => 'string'],
            'project_id' => ['type' => 'string'],
            'limit' => ['type' => 'integer', 'default' => 10],
        ]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $q = trim((string) ($arguments['query'] ?? ''));
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));
        $query = ProjectTask::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->orderByDesc('updated_at')
            ->limit($limit);
        if (! empty($arguments['project_id'])) {
            $query->where('project_id', (string) $arguments['project_id']);
        }
        if ($q !== '') {
            $query->where('title', 'like', '%'.$q.'%');
        }
        $rows = $query->get(['id', 'title', 'status', 'project_id', 'priority', 'ends_on']);

        return [
            'count' => $rows->count(),
            'tasks' => $rows->map(fn (ProjectTask $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status?->value ?? $t->status,
                'project_id' => $t->project_id,
                'priority' => $t->priority,
                'ends_on' => optional($t->ends_on)?->toDateString(),
            ])->all(),
        ];
    }
}
