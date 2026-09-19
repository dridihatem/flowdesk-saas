<?php

namespace App\AI\Nova\Project;

use App\AI\Nova\Agent\NovaContext;

/**
 * Builds a structured ProjectPlan preview. Never writes to the database —
 * PlanPreviewService / user confirmation owns persistence.
 */
class ProjectPlanner
{
    public function __construct(
        private TaskPlanner $tasks,
        private DependencyDetector $dependencies,
        private SkillAnalyzer $skills,
    ) {}

    /**
     * @param  array<string, mixed>  $requirements
     * @return array<string, mixed>
     */
    public function fromRequirements(array $requirements, NovaContext $context): array
    {
        $title = (string) ($requirements['title'] ?? $requirements['project']['title'] ?? 'New project from document');
        $description = (string) ($requirements['summary'] ?? $requirements['project']['description'] ?? '');
        $objectives = $requirements['objectives'] ?? [];
        $deliverables = $requirements['deliverables'] ?? [];
        if ($deliverables === [] && is_array($objectives)) {
            $deliverables = $objectives;
        }
        if ($deliverables === []) {
            $deliverables = ['Discovery', 'Implementation', 'QA', 'Launch'];
        }

        $taskList = $this->tasks->fromDeliverables(is_array($deliverables) ? $deliverables : []);
        $phases = [
            ['order' => 1, 'title' => 'Discovery', 'description' => 'Requirements and planning'],
            ['order' => 2, 'title' => 'Build', 'description' => 'Implementation'],
            ['order' => 3, 'title' => 'Launch', 'description' => 'QA and go-live'],
        ];

        $hours = array_sum(array_map(fn ($t) => (int) ($t['estimated_hours'] ?? 0), $taskList));
        $deps = $this->dependencies->detect($taskList);

        return [
            'project' => [
                'title' => $title,
                'description' => $description,
                'objective' => is_array($objectives) ? implode('; ', array_map('strval', $objectives)) : (string) $objectives,
                'priority' => 'high',
                'estimated_duration_days' => max(1, (int) ceil($hours / 8)),
            ],
            'phases' => $phases,
            'tasks' => $taskList,
            'milestones' => [
                ['title' => 'Kickoff', 'order' => 1],
                ['title' => 'MVP ready', 'order' => 2],
                ['title' => 'Launch', 'order' => 3],
            ],
            'dependencies' => $deps,
            'required_skills' => $this->skills->detect($title.' '.$description.' '.json_encode($deliverables)),
            'preview' => [
                'phase_count' => count($phases),
                'task_count' => count($taskList),
                'dependency_count' => count($deps),
                'estimated_hours' => $hours,
                'company_id' => $context->companyId,
            ],
            'requires_confirmation' => true,
        ];
    }
}
