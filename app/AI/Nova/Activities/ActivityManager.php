<?php

namespace App\AI\Nova\Activities;

use App\Events\Nova\NovaActivityCreated;
use App\Models\Nova\NovaActivity;
use App\Models\Nova\NovaRun;

class ActivityManager
{
    public function __construct(private ActivityPresenter $presenter) {}

    public function record(NovaRun $run, string $type, string $message, ?string $toolName = null, array $metadata = []): NovaActivity
    {
        $activity = $run->activities()->create([
            'type' => $type,
            'status' => $type === 'completed' || $type === 'error' ? 'done' : 'running',
            'message' => $this->sanitizeMessage($message),
            'tool_name' => $toolName,
            'metadata' => $metadata === [] ? null : $metadata,
            'started_at' => now(),
            'completed_at' => in_array($type, ['completed', 'error'], true) ? now() : null,
        ]);

        event(new NovaActivityCreated($activity, (string) $run->company_id));

        return $activity;
    }

    public function complete(NovaActivity $activity, ?string $message = null): NovaActivity
    {
        $activity->update([
            'status' => 'done',
            'message' => $message !== null ? $this->sanitizeMessage($message) : $activity->message,
            'completed_at' => now(),
        ]);

        event(new NovaActivityCreated($activity->fresh(), (string) $activity->run->company_id));

        return $activity->fresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function timeline(NovaRun $run): array
    {
        return $run->activities->map(fn (NovaActivity $a) => $this->presenter->present($a))->all();
    }

    private function sanitizeMessage(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        return mb_substr($message, 0, 240);
    }
}
