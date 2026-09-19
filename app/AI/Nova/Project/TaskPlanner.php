<?php

namespace App\AI\Nova\Project;

class TaskPlanner
{
    public function __construct(
        private PriorityAnalyzer $priorities,
        private SkillAnalyzer $skills,
    ) {}

    /**
     * @param  list<string>  $deliverables
     * @return list<array<string, mixed>>
     */
    public function fromDeliverables(array $deliverables): array
    {
        $tasks = [];
        $order = 1;
        foreach ($deliverables as $item) {
            $title = is_string($item) ? $item : (string) ($item['title'] ?? 'Task');
            $description = is_array($item) ? (string) ($item['description'] ?? '') : '';
            $tasks[] = [
                'order' => $order,
                'title' => $title,
                'description' => $description,
                'priority' => $this->priorities->infer($title, $description),
                'estimated_hours' => is_array($item) ? (int) ($item['estimated_hours'] ?? 4) : 4,
                'dependencies' => $order > 1 ? [$order - 1] : [],
                'required_skills' => $this->skills->detect($title.' '.$description),
            ];
            $order++;
        }

        return $tasks;
    }
}
