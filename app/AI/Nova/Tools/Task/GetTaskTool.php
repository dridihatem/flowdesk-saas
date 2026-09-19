<?php

namespace App\AI\Nova\Tools\Task;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectTask;

class GetTaskTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'tasks.get'; }
    public function description(): string { return 'Get a task by ID.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['task_id' => ['type' => 'string']], 'required' => ['task_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var ProjectTask $task */
        $task = $this->findInCompany(ProjectTask::class, (string) ($arguments['task_id'] ?? ''), $context);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status?->value ?? $task->status,
            'project_id' => $task->project_id,
            'priority' => $task->priority,
            'estimated_hours' => $task->estimated_hours,
            'assignee_id' => $task->assignee_id ?? $task->user_id ?? null,
        ];
    }
}
