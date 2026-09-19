<?php

namespace App\AI\Nova\Assignment;

use App\AI\Nova\Agent\NovaContext;
use App\Models\TeamAvailability;
use Illuminate\Support\Collection;

class AvailabilityAnalyzer
{
    /**
     * @param  list<int>  $userIds
     * @return array<int, float> user_id => available hours (next 7 days sum / 7)
     */
    public function averageDailyHours(NovaContext $context, array $userIds): array
    {
        $start = now()->toDateString();
        $end = now()->addDays(6)->toDateString();
        $rows = TeamAvailability::query()
            ->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->whereIn('user_id', $userIds)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('user_id');

        $out = [];
        foreach ($userIds as $userId) {
            $group = $rows->get($userId, collect());
            if ($group->isEmpty()) {
                $out[$userId] = 8.0; // default full day when no rows
                continue;
            }
            $out[$userId] = round(((float) $group->sum('available_hours')) / 7, 2);
        }

        return $out;
    }

    /**
     * Normalize availability to 0..1 (8h = 1.0).
     *
     * @param  array<int, float>  $hours
     * @return array<int, float>
     */
    public function scores(array $hours): array
    {
        $scores = [];
        foreach ($hours as $userId => $h) {
            $scores[$userId] = max(0, min(1, $h / 8));
        }

        return $scores;
    }
}
