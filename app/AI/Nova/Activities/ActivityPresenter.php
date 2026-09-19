<?php

namespace App\AI\Nova\Activities;

use App\Models\Nova\NovaActivity;

class ActivityPresenter
{
    /**
     * Short user-facing status only — never expose chain-of-thought.
     */
    public function present(NovaActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'run_id' => $activity->run_id,
            'type' => $activity->type,
            'status' => $activity->status,
            'message' => $activity->message,
            'tool_name' => $activity->tool_name,
            'started_at' => optional($activity->started_at)?->toIso8601String(),
            'completed_at' => optional($activity->completed_at)?->toIso8601String(),
        ];
    }
}
