<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteProjectFromGoogleCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $companyId,
        public string $eventId,
    ) {}

    public function handle(GoogleCalendarSyncService $sync): void
    {
        $company = Company::query()->find($this->companyId);
        if (! $company) {
            return;
        }
        if (! $sync->isConfigured($company)) {
            return;
        }
        $sync->deleteEventForCompany($company, $this->eventId, null);
    }
}
