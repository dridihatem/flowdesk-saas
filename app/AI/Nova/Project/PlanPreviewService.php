<?php

namespace App\AI\Nova\Project;

use App\AI\Nova\Agent\NovaContext;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDependency;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Persists a confirmed project plan. Never called without explicit user confirmation.
 */
class PlanPreviewService
{
    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    public function createFromConfirmedPlan(array $plan, NovaContext $context): array
    {
        if (! ($plan['confirmed'] ?? false)) {
            throw new RuntimeException('Project plan must be confirmed before creation.');
        }

        return DB::transaction(function () use ($plan, $context) {
            $projectData = $plan['project'] ?? [];
            $project = Project::query()->withoutGlobalScopes()->create([
                'company_id' => $context->companyId,
                'name' => (string) ($projectData['title'] ?? 'AI project'),
                'description' => (string) ($projectData['description'] ?? ''),
                'status' => ProjectStatus::Pending->value,
            ]);

            $phaseMap = [];
            foreach ($plan['phases'] ?? [] as $phase) {
                $row = ProjectPhase::query()->withoutGlobalScopes()->create([
                    'company_id' => $context->companyId,
                    'project_id' => $project->id,
                    'title' => (string) ($phase['title'] ?? 'Phase'),
                    'description' => $phase['description'] ?? null,
                    'order' => (int) ($phase['order'] ?? 0),
                ]);
                $phaseMap[(int) ($phase['order'] ?? 0)] = $row->id;
            }

            $taskMap = [];
            foreach ($plan['tasks'] ?? [] as $task) {
                $row = ProjectTask::query()->withoutGlobalScopes()->create([
                    'company_id' => $context->companyId,
                    'project_id' => $project->id,
                    'phase_id' => $phaseMap[(int) ($task['phase_order'] ?? 2)] ?? ($phaseMap[2] ?? null),
                    'title' => (string) ($task['title'] ?? 'Task'),
                    'description' => $task['description'] ?? null,
                    'status' => TaskStatus::Todo->value,
                    'priority' => $task['priority'] ?? 'medium',
                    'order' => (int) ($task['order'] ?? 0),
                    'estimated_hours' => (int) ($task['estimated_hours'] ?? 0),
                    'ai_generated' => true,
                    'ai_confidence' => $task['ai_confidence'] ?? 0.7,
                    'source_document_id' => $plan['source_document_id'] ?? null,
                    'source_page' => $task['source_page'] ?? null,
                    'source_section' => $task['source_section'] ?? null,
                    'source_text' => $task['source_text'] ?? null,
                ]);
                $taskMap[(int) ($task['order'] ?? 0)] = $row->id;
            }

            foreach ($plan['dependencies'] ?? [] as $dep) {
                $taskId = $taskMap[(int) ($dep['task_order'] ?? 0)] ?? null;
                $dependsOn = $taskMap[(int) ($dep['depends_on_order'] ?? 0)] ?? null;
                if ($taskId && $dependsOn && $taskId !== $dependsOn) {
                    ProjectTaskDependency::query()->withoutGlobalScopes()->create([
                        'company_id' => $context->companyId,
                        'task_id' => $taskId,
                        'depends_on_task_id' => $dependsOn,
                    ]);
                }
            }

            return [
                'project_id' => $project->id,
                'phases_created' => count($phaseMap),
                'tasks_created' => count($taskMap),
            ];
        });
    }
}
