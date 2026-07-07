<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleCalendarSyncService
{
    public function __construct(
        private GoogleCalendarSyncService $googleCalendar,
    ) {}

    /**
     * Create or update a calendar event for a property viewing.
     */
    public function syncPropertyViewing(
        Company $company,
        User $user,
        string $viewingId,
        string $propertyTitle,
        ?string $zone,
        ?string $scheduledAt,
        ?string $clientName,
        string $moduleSlug,
    ): ?string {
        if (! Schema::hasColumn('module_property_viewings', 'calendar_event_id')) {
            return null;
        }

        if ($scheduledAt === null || trim($scheduledAt) === '') {
            $this->detachViewingCalendarEvent($company, $viewingId);

            return null;
        }

        $startsOn = Carbon::parse($scheduledAt)->toDateString();
        $title = __('module_calendar_viewing_title', ['property' => $propertyTitle]);
        $description = collect([
            $zone ? __('module_calendar_viewing_zone', ['zone' => $zone]) : null,
            $clientName ? __('module_calendar_viewing_client', ['client' => $clientName]) : null,
        ])->filter()->implode("\n");

        $existingId = DB::table('module_property_viewings')
            ->where('company_id', $company->id)
            ->where('id', $viewingId)
            ->value('calendar_event_id');

        $event = null;
        if (is_string($existingId) && $existingId !== '') {
            $event = WorkspaceCalendarEvent::query()
                ->where('company_id', $company->id)
                ->whereKey($existingId)
                ->first();
        }

        if ($event) {
            $event->update([
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'starts_on' => $startsOn,
                'ends_on' => null,
                'kind' => 'appointment',
                'source_type' => 'module_property_viewing',
                'source_id' => $viewingId,
            ]);
        } else {
            $event = WorkspaceCalendarEvent::query()->create([
                'company_id' => $company->id,
                'created_by' => $user->id,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'starts_on' => $startsOn,
                'ends_on' => null,
                'kind' => 'appointment',
                'source_type' => 'module_property_viewing',
                'source_id' => $viewingId,
            ]);
        }

        DB::table('module_property_viewings')
            ->where('company_id', $company->id)
            ->where('id', $viewingId)
            ->update(['calendar_event_id' => $event->id]);

        $this->googleCalendar->syncWorkspaceEvent($event);

        return $event->id;
    }

    public function detachViewingCalendarEvent(Company $company, string $viewingId): void
    {
        if (! Schema::hasColumn('module_property_viewings', 'calendar_event_id')) {
            return;
        }

        $eventId = DB::table('module_property_viewings')
            ->where('company_id', $company->id)
            ->where('id', $viewingId)
            ->value('calendar_event_id');

        if (! is_string($eventId) || $eventId === '') {
            return;
        }

        $event = WorkspaceCalendarEvent::query()
            ->where('company_id', $company->id)
            ->whereKey($eventId)
            ->first();

        if ($event) {
            if (filled($event->google_calendar_event_id)) {
                $this->googleCalendar->deleteEventForCompany($company, (string) $event->google_calendar_event_id);
            }
            $event->delete();
        }

        DB::table('module_property_viewings')
            ->where('company_id', $company->id)
            ->where('id', $viewingId)
            ->update(['calendar_event_id' => null]);
    }
}
