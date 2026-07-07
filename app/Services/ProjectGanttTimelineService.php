<?php

namespace App\Services;

use App\Models\ProjectTask;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ProjectGanttTimelineService
{
    /**
     * @param  Collection<int, ProjectTask>|iterable<ProjectTask>  $tasks
     * @return array{start: CarbonImmutable, end: CarbonImmutable, rangeDays: int, rows: list<array{task: ProjectTask, start: CarbonImmutable, end: CarbonImmutable, offsetDays: float, spanDays: float, offsetPct: float, spanPct: float}>}
     */
    public function build(iterable $tasks): array
    {
        $today = CarbonImmutable::today();
        /** @var list<array{task: ProjectTask, start: CarbonImmutable, end: CarbonImmutable}> $rows */
        $rows = [];
        foreach ($tasks as $t) {
            $start = $t->starts_on?->toImmutable() ?? CarbonImmutable::parse($t->created_at)->startOfDay();
            $end = $t->ends_on?->toImmutable() ?? $start->addDays(2);
            if ($end->lessThan($start)) {
                $end = $start;
            }
            $rows[] = ['task' => $t, 'start' => $start, 'end' => $end];
        }

        if ($rows === []) {
            $start = $today;
            $end = $today->addDays(14);

            return [
                'start' => $start,
                'end' => $end,
                'rangeDays' => 15,
                'rows' => [],
            ];
        }

        $min = $today;
        $max = $today->addDays(1);
        foreach ($rows as $r) {
            $min = $min->min($r['start']);
            $max = $max->max($r['end']);
        }
        $max = $max->addDay();
        $rawRange = (int) ($min->diffInDays($max) + 1);
        $rangeDays = max(7, $rawRange);
        $chartEnd = $min->addDays($rangeDays - 1);

        $timelineRows = [];
        foreach ($rows as $r) {
            $offsetDays = (float) $min->diffInDays($r['start']);
            $spanDays = max(1, (float) $r['start']->diffInDays($r['end']) + 1);
            $timelineRows[] = [
                'task' => $r['task'],
                'start' => $r['start'],
                'end' => $r['end'],
                'offsetDays' => $offsetDays,
                'spanDays' => $spanDays,
                'offsetPct' => $rangeDays > 0 ? min(100, ($offsetDays / $rangeDays) * 100) : 0,
                'spanPct' => $rangeDays > 0 ? min(100, ($spanDays / $rangeDays) * 100) : 0,
            ];
        }

        return [
            'start' => $min,
            'end' => $chartEnd,
            'rangeDays' => $rangeDays,
            'rows' => $timelineRows,
        ];
    }
}
