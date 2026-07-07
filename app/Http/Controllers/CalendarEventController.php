<?php

namespace App\Http\Controllers;

use App\Enums\CalendarMeetingLinkType;
use App\Models\Company;
use App\Models\Project;
use App\Models\WorkspaceCalendarEvent;
use App\Services\CalendarMeetingLinkService;
use App\Services\GoogleCalendarSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarEventController extends Controller
{
    public function store(
        Request $request,
        CalendarMeetingLinkService $meetings,
        GoogleCalendarSyncService $google,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user && $user->can('workspace.view_dashboard'), 403);

        $company = $user->company;
        abort_if(! $company instanceof Company, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'kind' => ['required', 'string', Rule::in(['meeting', 'appointment', 'reminder', 'note'])],
            'meeting_link_type' => ['nullable', 'string', Rule::enum(CalendarMeetingLinkType::class)],
            'meeting_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $linkType = CalendarMeetingLinkType::tryFrom((string) ($validated['meeting_link_type'] ?? 'none'))
            ?? CalendarMeetingLinkType::None;

        if (! in_array($validated['kind'], ['meeting', 'appointment'], true)) {
            $linkType = CalendarMeetingLinkType::None;
        }

        $meetingFields = $meetings->resolveForNewEvent(
            $company,
            $linkType,
            $validated['meeting_url'] ?? null,
            $validated['title'],
            $validated['date'],
        );

        $event = WorkspaceCalendarEvent::query()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_on' => $validated['date'],
            'ends_on' => $validated['end_date'] ?? null,
            'kind' => $validated['kind'],
            ...$meetingFields,
        ]);

        if ($linkType === CalendarMeetingLinkType::GoogleMeet && $google->isConnected($company)) {
            $meetings->finalizeGoogleMeet($event);
            $event->refresh();
        }

        $type = $event->calendarType();
        $meetingUrl = $meetings->publicMeetingUrl($event);

        return response()->json([
            'event' => [
                'id' => 'custom-'.$event->id,
                'type' => $type,
                'title' => $event->title,
                'date' => $event->starts_on->toDateString(),
                'end_date' => $event->ends_on?->toDateString(),
                'subtitle' => $this->eventSubtitle($event),
                'url' => $meetingUrl,
                'meeting_url' => $meetingUrl,
                'meeting_link_type' => $event->meeting_link_type,
                'color' => $type === 'meeting' ? 'indigo' : 'violet',
                'can_delete' => true,
                'google_synced' => false,
                'sync_kind' => 'custom',
            ],
        ], 201);
    }

    public function destroy(Request $request, WorkspaceCalendarEvent $event, GoogleCalendarSyncService $google): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('workspace.view_dashboard'), 403);

        $company = $user->company;
        abort_if(! $company instanceof Company || $event->company_id !== $company->id, 403);

        if (filled($event->google_calendar_event_id)) {
            $google->deleteEventForCompany($company, (string) $event->google_calendar_event_id);
        }

        $event->delete();

        return response()->json(['ok' => true]);
    }

    public function syncGoogle(Request $request, GoogleCalendarSyncService $google): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('workspace.view_dashboard'), 403);

        $company = $user->company;
        abort_if(! $company instanceof Company, 403);

        if (! $google->isConnected($company)) {
            return response()->json(['message' => __('calendar_google_not_connected')], 422);
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:300'],
            'date' => ['required', 'date'],
            'subtitle' => ['nullable', 'string', 'max:500'],
        ]);

        $ok = $google->syncByCompositeEventId(
            $company,
            $validated['event_id'],
            $validated['title'],
            $validated['date'],
            $validated['subtitle'] ?? null,
        );

        if (! $ok) {
            return response()->json(['message' => __('calendar_google_sync_failed')], 422);
        }

        $googleSynced = true;
        if (str_starts_with($validated['event_id'], 'custom-')) {
            $custom = WorkspaceCalendarEvent::query()
                ->where('company_id', $company->id)
                ->find(substr($validated['event_id'], 7));
            $googleSynced = $custom && filled($custom->google_calendar_event_id);
        } elseif (preg_match('/^project-(?:created|deadline)-(.+)$/', $validated['event_id'], $matches) === 1) {
            $googleSynced = Project::query()
                ->where('company_id', $company->id)
                ->whereKey($matches[1])
                ->whereNotNull('google_calendar_event_id')
                ->exists();
        }

        return response()->json([
            'ok' => true,
            'google_synced' => $googleSynced,
            'message' => __('calendar_google_synced'),
        ]);
    }

    private function eventSubtitle(WorkspaceCalendarEvent $event): ?string
    {
        $parts = [];
        if ($event->description) {
            $parts[] = mb_substr(strip_tags((string) $event->description), 0, 80);
        }
        if (filled($event->meeting_url)) {
            $parts[] = __('calendar_meeting_link_short');
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
