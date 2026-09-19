<?php

namespace App\AI\Nova\Tools\Assignment;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Assignment\AssignmentEngine;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\ProjectTask;

class RecommendAssignmentTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private AssignmentEngine $engine) {}

    public function name(): string { return 'assignments.recommend'; }
    public function description(): string { return 'Recommend team members for a task using deterministic scoring.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'task_id' => ['type' => 'string'],
            'query' => ['type' => 'string'],
            'required_skills' => ['type' => 'array', 'items' => ['type' => 'string']],
        ]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $task = null;
        if (! empty($arguments['task_id'])) {
            $task = $this->findInCompany(ProjectTask::class, (string) $arguments['task_id'], $context);
        }

        return $this->engine->recommend($context, $task, is_array($arguments['required_skills'] ?? null) ? $arguments['required_skills'] : []);
    }
}
