<?php

namespace App\Services;

use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Models\Project;
use App\Models\ProjectTask;

class ProjectInvoiceLinesService
{
    /**
     * Suggested invoice line rows (minor unit amounts) from project price + tasks.
     *
     * @return list<array{description: string, quantity: int, unit_amount: int}>
     */
    public function suggestedLines(Project $project): array
    {
        $lines = [];

        $final = $project->final_price;
        $nego = $project->negotiated_price;

        if ($final !== null && $final > 0) {
            $lines[] = [
                'description' => $project->title.' — '.__('Final project price'),
                'quantity' => 1,
                'unit_amount' => (int) $final,
            ];
        } elseif ($nego !== null && $nego > 0) {
            $lines[] = [
                'description' => $project->title.' — '.__('Estimated / negotiated project price'),
                'quantity' => 1,
                'unit_amount' => (int) $nego,
            ];
        }

        $project->loadMissing('tasks');

        /** @var ProjectTask $task */
        foreach ($project->tasks->sortBy('sort_order') as $task) {
            if (! $task->billable) {
                continue;
            }

            $scope = $task->scope instanceof TaskScope ? $task->scope : TaskScope::tryFrom((string) $task->scope) ?? TaskScope::Core;
            $mode = $task->price_mode instanceof TaskPriceMode ? $task->price_mode : TaskPriceMode::tryFrom((string) $task->price_mode) ?? TaskPriceMode::Bundled;

            $prefix = $scope === TaskScope::Extra ? __('Extra: ').' ' : '';

            if ($mode === TaskPriceMode::Additive) {
                $lines[] = [
                    'description' => $prefix.$task->title,
                    'quantity' => 1,
                    'unit_amount' => (int) ($task->amount_cents ?? 0),
                ];
            } elseif ($mode === TaskPriceMode::Bundled) {
                $lines[] = [
                    'description' => $prefix.$task->title.' ('.__('included in project price').')',
                    'quantity' => 1,
                    'unit_amount' => 0,
                ];
            }
        }

        if ($lines === []) {
            $lines[] = [
                'description' => '',
                'quantity' => 1,
                'unit_amount' => 0,
            ];
        }

        return $lines;
    }
}
