<?php

namespace App\AI\Nova\Assignment;

use App\AI\Nova\Agent\NovaContext;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSkill;
use App\Models\Skill;

/**
 * Deterministic assignment scoring. The LLM explains — it does not pick arbitrarily.
 */
class AssignmentEngine
{
    public function __construct(
        private SkillMatcher $skills,
        private WorkloadAnalyzer $workload,
        private AvailabilityAnalyzer $availability,
    ) {}

    /**
     * @param  list<string>  $requiredSkillNames
     * @return array<string, mixed>
     */
    public function recommend(NovaContext $context, ?ProjectTask $task = null, array $requiredSkillNames = []): array
    {
        if ($task !== null && $requiredSkillNames === []) {
            $requiredSkillNames = ProjectTaskSkill::query()
                ->withoutGlobalScopes()
                ->where('task_id', $task->id)
                ->with('skill')
                ->get()
                ->pluck('skill.name')
                ->filter()
                ->values()
                ->all();
        }

        $weights = $this->weights($context);
        $skillRows = $this->skills->score($context, $requiredSkillNames);
        $userIds = $skillRows->pluck('user_id')->all();
        $availabilityHours = $this->availability->averageDailyHours($context, $userIds);
        $availabilityScores = $this->availability->scores($availabilityHours);
        $capacityScores = $this->workload->capacityScores($context, $userIds);

        $candidates = $skillRows->map(function (array $row) use ($weights, $availabilityScores, $capacityScores, $availabilityHours) {
            $uid = $row['user_id'];
            $skill = (float) $row['skill_score'];
            $avail = (float) ($availabilityScores[$uid] ?? 0.5);
            $workload = (float) ($capacityScores[$uid] ?? 0.5);
            $experience = $skill; // proxy until years tracked per aggregate
            $projectContext = 0.5;
            $total = ($skill * $weights['skill'])
                + ($avail * $weights['availability'])
                + ($workload * $weights['workload'])
                + ($experience * $weights['experience'])
                + ($projectContext * $weights['project_context']);

            return [
                'user_id' => $uid,
                'name' => $row['name'],
                'score' => round($total, 4),
                'breakdown' => [
                    'skill' => round($skill, 4),
                    'availability' => round($avail, 4),
                    'workload_capacity' => round($workload, 4),
                    'experience' => round($experience, 4),
                    'project_context' => $projectContext,
                ],
                'matched_skills' => $row['matched'],
                'available_hours_per_day' => $availabilityHours[$uid] ?? 8.0,
            ];
        })->sortByDesc('score')->values();

        $top = $candidates->first();

        return [
            'mode' => $context->assignmentMode,
            'task_id' => $task?->id,
            'required_skills' => $requiredSkillNames,
            'weights' => $weights,
            'recommendation' => $top,
            'candidates' => $candidates->take(5)->all(),
            'explanation' => $top
                ? sprintf(
                    'I recommend %s (score %.0f%%). Matched skills: %s. Available ~%.1fh/day.',
                    $top['name'],
                    $top['score'] * 100,
                    $top['matched_skills'] !== [] ? implode(', ', $top['matched_skills']) : 'general capacity',
                    $top['available_hours_per_day']
                )
                : 'No team members found for assignment.',
            'auto_assign_allowed' => $context->assignmentMode === 'automatic',
        ];
    }

    /**
     * @return array{skill: float, availability: float, workload: float, experience: float, project_context: float}
     */
    private function weights(NovaContext $context): array
    {
        $settings = $context->company->settings?->ai_agent ?? [];
        $custom = is_array($settings['assignment_weights'] ?? null) ? $settings['assignment_weights'] : [];

        return [
            'skill' => (float) ($custom['skill'] ?? 0.40),
            'availability' => (float) ($custom['availability'] ?? 0.20),
            'workload' => (float) ($custom['workload'] ?? 0.20),
            'experience' => (float) ($custom['experience'] ?? 0.10),
            'project_context' => (float) ($custom['project_context'] ?? 0.10),
        ];
    }
}
