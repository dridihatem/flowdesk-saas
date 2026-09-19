<?php

namespace App\AI\Nova\Tools\Task;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\Validator;

class UpdateTaskTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'tasks.update'; }
    public function description(): string { return 'Update a task.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'task_id' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'priority' => ['type' => 'string'],
        ], 'required' => ['task_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var ProjectTask $task */
        $task = $this->findInCompany(ProjectTask::class, (string) ($arguments['task_id'] ?? ''), $context);
        $data = Validator::make($arguments, [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:32'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:16'],
        ])->validate();
        $task->fill(collect($data)->only(['title', 'description', 'status', 'priority'])->all());
        $task->save();

        return ['id' => $task->id, 'title' => $task->title, 'status' => $task->status?->value ?? $task->status];
    }
}
