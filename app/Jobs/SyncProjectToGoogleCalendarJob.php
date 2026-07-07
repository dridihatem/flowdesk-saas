<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncProjectToGoogleCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $projectId,
    ) {}

    public function handle(GoogleCalendarSyncService $sync): void
    {
        $project = Project::query()->withoutGlobalScopes()->find($this->projectId);
        if (! $project) {
            return;
        }
        if (! $project->company_id) {
            return;
        }
        $project->load('company');
        if (! $sync->isConfigured($project->company)) {
            return;
        }
        $sync->syncProject($project);
    }
}
