<?php

namespace App\AI\Nova\Project;

class DependencyDetector
{
    /**
     * @param  list<array<string, mixed>>  $tasks
     * @return list<array{task_order: int, depends_on_order: int}>
     */
    public function detect(array $tasks): array
    {
        $deps = [];
        foreach ($tasks as $task) {
            $order = (int) ($task['order'] ?? 0);
            foreach ($task['dependencies'] ?? [] as $dep) {
                $deps[] = [
                    'task_order' => $order,
                    'depends_on_order' => (int) $dep,
                ];
            }
            // Heuristic: sequential dependency when none provided.
            if ($order > 1 && empty($task['dependencies'])) {
                $deps[] = ['task_order' => $order, 'depends_on_order' => $order - 1];
            }
        }

        return $deps;
    }
}
