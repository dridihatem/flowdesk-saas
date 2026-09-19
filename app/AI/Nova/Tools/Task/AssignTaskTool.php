<?php

namespace App\AI\Nova\Tools\Task;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AssignTaskTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'tasks.assign';
    }

    public function description(): string
    {
        return 'Assign a task to a team member (respects assignment_mode).';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'task_id' => ['type' => 'string'],
                'user_id' => ['type' => 'integer'],
            ],
            'required' => ['task_id', 'user_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        if ($context->assignmentMode === 'ask') {
            throw new RuntimeException('assignment_mode=ask: confirmation required before assigning.');
        }

        $data = Validator::make($arguments, [
            'task_id' => ['required', 'string'],
            'user_id' => ['required', 'integer'],
        ])->validate();

        /** @var ProjectTask $task */
        $task = $this->findInCompany(ProjectTask::class, $data['task_id'], $context);
        $user = User::query()->whereKey($data['user_id'])->where('company_id', $context->companyId)->first();
        if ($user === null) {
            throw new RuntimeException('Team member not found in this workspace.');
        }

        $task->assignee_id = $user->id;
        $task->save();

        return ['task_id' => $task->id, 'assigned_to' => $user->id, 'assigned_name' => $user->name];
    }
}
