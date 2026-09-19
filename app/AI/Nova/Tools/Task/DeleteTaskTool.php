<?php

namespace App\AI\Nova\Tools\Task;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectTask;

class DeleteTaskTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'tasks.delete'; }
    public function description(): string { return 'Delete a task (HIGH risk).'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['task_id' => ['type' => 'string']], 'required' => ['task_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var ProjectTask $task */
        $task = $this->findInCompany(ProjectTask::class, (string) ($arguments['task_id'] ?? ''), $context);
        $id = $task->id;
        $title = $task->title;
        $task->delete();

        return ['deleted' => true, 'id' => $id, 'title' => $title];
    }
}
