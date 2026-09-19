<?php

namespace App\AI\Nova\Tools\Task;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\Validator;

class CreateTaskTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'tasks.create'; }
    public function description(): string { return 'Create a task on a project.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'project_id' => ['type' => 'string'],
            'title' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'priority' => ['type' => 'string'],
            'estimated_hours' => ['type' => 'integer'],
        ], 'required' => ['project_id', 'title']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $data = Validator::make($arguments, [
            'project_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'max:16'],
            'estimated_hours' => ['nullable', 'integer', 'min:0'],
        ])->validate();

        $this->findInCompany(Project::class, $data['project_id'], $context);

        $task = ProjectTask::query()->withoutGlobalScopes()->create([
            'company_id' => $context->companyId,
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => TaskStatus::Todo->value,
            'priority' => $data['priority'] ?? 'medium',
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'ai_generated' => false,
        ]);

        return ['id' => $task->id, 'title' => $task->title, 'project_id' => $task->project_id];
    }
}
