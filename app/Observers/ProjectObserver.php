<?php

namespace App\Observers;

use App\Jobs\DeleteProjectFromGoogleCalendarJob;
use App\Jobs\SyncProjectToGoogleCalendarJob;
use App\Models\Project;

class ProjectObserver
{
    public function created(Project $project): void
    {
        if ((string) $project->id === '') {
            return;
        }
        SyncProjectToGoogleCalendarJob::dispatch((string) $project->id);
    }

    public function updated(Project $project): void
    {
        if (! $this->wantsCalendarSync($project)) {
            return;
        }
        SyncProjectToGoogleCalendarJob::dispatch((string) $project->id);
    }

    public function deleted(Project $project): void
    {
        if (empty($project->google_calendar_event_id)) {
            return;
        }
        DeleteProjectFromGoogleCalendarJob::dispatch(
            (string) $project->company_id,
            (string) $project->google_calendar_event_id,
        );
    }

    private function wantsCalendarSync(Project $project): bool
    {
        if (! $project->wasChanged(['title', 'final_deadline'])) {
            return false;
        }

        return true;
    }
}
