<?php

namespace App\AI\Nova\Assignment;

use App\AI\Nova\Agent\NovaContext;
use App\Enums\TaskStatus;
use App\Models\ProjectTask;
use App\Models\User;

class WorkloadAnalyzer
{
    public function __construct(private AvailabilityAnalyzer $availability) {}

    /**
     * @return array<string, mixed>
     */
    public function companySnapshot(NovaContext $context): array
    {
        $users = User::query()
            ->where('company_id', $context->companyId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['company_admin', 'team_member']))
            ->get();

        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $available = $this->availability->averageDailyHours($context, $userIds);

        $members = [];
        foreach ($users as $user) {
            $assignedHours = (float) ProjectTask::query()
                ->withoutGlobalScopes()
                ->where('company_id', $context->companyId)
                ->where('assignee_id', $user->id)
                ->whereNot('status', TaskStatus::Done)
                ->sum('estimated_hours');

            $availWeek = ($available[(int) $user->id] ?? 8.0) * 5;
            $pct = $availWeek > 0 ? round(($assignedHours / $availWeek) * 100, 1) : 0.0;
            $members[] = [
                'user_id' => (int) $user->id,
                'name' => $user->name,
                'assigned_hours' => $assignedHours,
                'available_hours' => $availWeek,
                'workload_percent' => $pct,
                'overloaded' => $pct >= 90,
            ];
        }

        return [
            'members' => $members,
            'overloaded' => array_values(array_filter($members, fn ($m) => $m['overloaded'])),
            'underutilized' => array_values(array_filter($members, fn ($m) => $m['workload_percent'] < 40)),
        ];
    }

    /**
     * @return array<int, float> user_id => 0..1 (higher = more capacity)
     */
    public function capacityScores(NovaContext $context, array $userIds): array
    {
        $snapshot = collect($this->companySnapshot($context)['members'])->keyBy('user_id');
        $scores = [];
        foreach ($userIds as $userId) {
            $pct = (float) ($snapshot[$userId]['workload_percent'] ?? 0);
            $scores[$userId] = max(0, min(1, 1 - ($pct / 100)));
        }

        return $scores;
    }
}
