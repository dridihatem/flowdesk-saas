<?php

namespace App\Services;

use App\Enums\CalendarMeetingLinkType;
use App\Models\Company;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CalendarMeetingLinkService
{
    public function __construct(
        private ZoomMeetingService $zoom,
        private GoogleCalendarSyncService $google,
    ) {}

    /**
     * @return array{
     *   meeting_link_type: string,
     *   meeting_url: ?string,
     *   zoom_meeting_id: ?string,
     *   google_meet_url: ?string,
     * }
     */
    public function resolveForNewEvent(
        Company $company,
        CalendarMeetingLinkType $linkType,
        ?string $manualUrl,
        string $title,
        string $startDate,
    ): array {
        $base = [
            'meeting_link_type' => $linkType->value,
            'meeting_url' => null,
            'zoom_meeting_id' => null,
            'google_meet_url' => null,
        ];

        return match ($linkType) {
            CalendarMeetingLinkType::None => $base,
            CalendarMeetingLinkType::Url => array_merge($base, [
                'meeting_url' => $this->normalizeUrl($manualUrl),
            ]),
            CalendarMeetingLinkType::Zoom => $this->createZoomLink($company, $base, $title, $startDate),
            CalendarMeetingLinkType::GoogleMeet => $base,
        };
    }

    public function finalizeGoogleMeet(WorkspaceCalendarEvent $event): void
    {
        if ($event->meeting_link_type !== CalendarMeetingLinkType::GoogleMeet->value) {
            return;
        }

        $event->loadMissing('company');
        $company = $event->company;
        if (! $company) {
            return;
        }

        $meetUrl = $this->google->createMeetLinkForWorkspaceEvent($event);
        if ($meetUrl === null) {
            return;
        }

        $event->forceFill([
            'google_meet_url' => $meetUrl,
            'meeting_url' => $meetUrl,
        ])->saveQuietly();
    }

    public function publicMeetingUrl(WorkspaceCalendarEvent $event): ?string
    {
        if (filled($event->meeting_url)) {
            return (string) $event->meeting_url;
        }

        if (filled($event->google_meet_url)) {
            return (string) $event->google_meet_url;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    private function createZoomLink(Company $company, array $base, string $title, string $startDate): array
    {
        if (! $this->zoom->isConfigured($company)) {
            throw ValidationException::withMessages([
                'meeting_link_type' => __('calendar_zoom_not_configured'),
            ]);
        }

        $start = Carbon::parse($startDate.' 09:00:00', config('app.timezone', 'UTC'));
        $created = $this->zoom->createScheduledMeeting(
            $company,
            $title,
            $start->toIso8601String(),
        );

        if ($created === null || ($created['join_url'] ?? '') === '') {
            throw ValidationException::withMessages([
                'meeting_link_type' => __('calendar_zoom_create_failed'),
            ]);
        }

        return array_merge($base, [
            'meeting_url' => $created['join_url'],
            'zoom_meeting_id' => $created['id'] ?? null,
        ]);
    }

    private function normalizeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            throw ValidationException::withMessages([
                'meeting_url' => __('calendar_meeting_url_required'),
            ]);
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'meeting_url' => __('calendar_meeting_url_invalid'),
            ]);
        }

        return $url;
    }
}
