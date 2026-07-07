<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Project;
use App\Models\WorkspaceCalendarEvent;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GoogleCalendarSyncService
{
    public function isConfigured(Company $company): bool
    {
        $token = $this->refreshTokenFor($company);

        return $token !== null && $token !== '';
    }

    public function refreshTokenFor(Company $company): ?string
    {
        $s = $company->settings;
        if (! $s) {
            return null;
        }
        $enc = $s->google_calendar_refresh_token_encrypted;
        if (empty($enc)) {
            return null;
        }

        try {
            return Crypt::decryptString($enc);
        } catch (Throwable) {
            return null;
        }
    }

    public function makeClient(Company $company): ?GoogleClient
    {
        $refresh = $this->refreshTokenFor($company);
        if ($refresh === null) {
            return null;
        }

        $id = (string) config('services.google.client_id');
        $secret = (string) config('services.google.client_secret');
        if ($id === '' || $secret === '') {
            return null;
        }

        $client = new GoogleClient;
        $client->setClientId($id);
        $client->setClientSecret($secret);
        $client->setAccessType('offline');
        $client->addScope(Calendar::CALENDAR_EVENTS);

        try {
            $client->fetchAccessTokenWithRefreshToken($refresh);
        } catch (Throwable $e) {
            Log::warning('google.calendar.token_refresh', ['message' => $e->getMessage(), 'company_id' => $company->id]);

            return null;
        }

        if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
            try {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } catch (Throwable $e) {
                Log::warning('google.calendar.token_refresh2', ['message' => $e->getMessage(), 'company_id' => $company->id]);

                return null;
            }
        }

        return $client;
    }

    public function saveRefreshToken(Company $company, string $refreshToken, ?string $email = null): void
    {
        $settings = $company->settings()->firstOrCreate();
        $settings->update([
            'google_calendar_refresh_token_encrypted' => Crypt::encryptString($refreshToken),
            'google_calendar_connected_email' => $email,
            'google_calendar_connected_at' => now(),
        ]);
    }

    public function disconnect(Company $company): void
    {
        $s = $company->settings;
        if (! $s) {
            return;
        }
        $s->update([
            'google_calendar_refresh_token_encrypted' => null,
            'google_calendar_connected_email' => null,
            'google_calendar_connected_at' => null,
        ]);
    }

    public function syncProject(Project $project): void
    {
        $project->loadMissing('company');
        $company = $project->company;
        if (! $company) {
            return;
        }

        $client = $this->makeClient($company);
        if (! $client) {
            return;
        }

        $cal = new Calendar($client);
        $tz = (string) config('app.timezone', 'UTC');

        $summary = (string) config('app.name', 'Flowqil');
        $summary .= ' — '.mb_substr((string) $project->title, 0, 200);

        $startDate = $project->created_at?->timezone($tz)->toDateString()
            ?? now($tz)->toDateString();

        if ($project->final_deadline !== null) {
            $endDate = $project->final_deadline->copy()->addDay()->toDateString();
            if ($endDate <= $startDate) {
                $endDate = Carbon::parse($startDate, $tz)->addDay()->toDateString();
            }
        } else {
            $endDate = Carbon::parse($startDate, $tz)->addDay()->toDateString();
        }

        $bodyLines = [__('Project calendar body created')];
        if ($project->final_deadline !== null) {
            $bodyLines[] = __('Deadline: :date', ['date' => $project->final_deadline->toDateString()]);
        } else {
            $bodyLines[] = __('No deadline set yet.');
        }
        $url = $project->id ? url('/projects/'.$project->id) : null;
        if (is_string($url) && $url !== '') {
            $bodyLines[] = $url;
        }
        $description = implode("\n", $bodyLines);

        $event = new Event;
        $event->setSummary($summary);
        $event->setDescription($description);
        $start = new EventDateTime;
        $start->setDate($startDate);
        $event->setStart($start);
        $end = new EventDateTime;
        $end->setDate($endDate);
        $event->setEnd($end);

        try {
            if (! empty($project->google_calendar_event_id)) {
                $cal->events->update('primary', (string) $project->google_calendar_event_id, $event);
            } else {
                $created = $cal->events->insert('primary', $event, ['sendUpdates' => 'none']);
                if ($created->getId()) {
                    $project->forceFill(['google_calendar_event_id' => $created->getId()])->saveQuietly();
                }
            }
        } catch (Throwable $e) {
            Log::warning('google.calendar.sync', [
                'message' => $e->getMessage(),
                'project_id' => $project->id,
            ]);
        }
    }

    public function deleteProjectEvent(Project $project): void
    {
        if (empty($project->google_calendar_event_id)) {
            return;
        }
        $project->loadMissing('company');
        $company = $project->company;
        if (! $company) {
            return;
        }
        $this->deleteEventForCompany($company, (string) $project->google_calendar_event_id, (string) $project->id);
    }

    public function deleteEventForCompany(Company $company, string $eventId, ?string $logProjectId = null): void
    {
        if ($eventId === '') {
            return;
        }
        $client = $this->makeClient($company);
        if (! $client) {
            return;
        }
        $cal = new Calendar($client);
        try {
            $cal->events->delete('primary', $eventId, ['sendUpdates' => 'none']);
        } catch (Throwable $e) {
            Log::warning('google.calendar.delete', [
                'message' => $e->getMessage(),
                'project_id' => $logProjectId,
                'company_id' => $company->id,
            ]);
        }
    }

    public function isConnected(Company $company): bool
    {
        return $this->isConfigured($company);
    }

    public function syncWorkspaceEvent(WorkspaceCalendarEvent $event, bool $withMeet = false): bool
    {
        $event->loadMissing('company');
        $company = $event->company;
        if (! $company) {
            return false;
        }

        $client = $this->makeClient($company);
        if (! $client) {
            return false;
        }

        $cal = new Calendar($client);
        $tz = (string) config('app.timezone', 'UTC');
        $startDate = $event->starts_on->toDateString();
        $endDate = $event->ends_on?->copy()->addDay()->toDateString()
            ?? Carbon::parse($startDate, $tz)->addDay()->toDateString();

        if ($endDate <= $startDate) {
            $endDate = Carbon::parse($startDate, $tz)->addDay()->toDateString();
        }

        $description = $this->workspaceEventDescription($event);

        $googleEvent = new Event;
        $googleEvent->setSummary(mb_substr((string) $event->title, 0, 200));
        if ($description !== '') {
            $googleEvent->setDescription($description);
        }

        $start = new EventDateTime;
        $start->setDate($startDate);
        $googleEvent->setStart($start);

        $end = new EventDateTime;
        $end->setDate($endDate);
        $googleEvent->setEnd($end);

        $insertOptions = ['sendUpdates' => 'none'];
        if ($withMeet || $event->meeting_link_type === 'google_meet') {
            $createRequest = new CreateConferenceRequest;
            $createRequest->setRequestId((string) Str::ulid());
            $solution = new ConferenceSolutionKey;
            $solution->setType('hangoutsMeet');
            $createRequest->setConferenceSolutionKey($solution);

            $conferenceData = new ConferenceData;
            $conferenceData->setCreateRequest($createRequest);
            $googleEvent->setConferenceData($conferenceData);
            $insertOptions['conferenceDataVersion'] = 1;
        }

        try {
            if (! empty($event->google_calendar_event_id)) {
                $cal->events->update('primary', (string) $event->google_calendar_event_id, $googleEvent, $insertOptions);
                $saved = $googleEvent;
            } else {
                $saved = $cal->events->insert('primary', $googleEvent, $insertOptions);
                if ($saved->getId()) {
                    $event->forceFill(['google_calendar_event_id' => $saved->getId()])->saveQuietly();
                }
            }

            if ($withMeet || $event->meeting_link_type === 'google_meet') {
                $meetUrl = $this->extractMeetUrl($saved);
                if ($meetUrl !== null) {
                    $event->forceFill([
                        'google_meet_url' => $meetUrl,
                        'meeting_url' => $meetUrl,
                    ])->saveQuietly();
                }
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('google.calendar.workspace_event', [
                'message' => $e->getMessage(),
                'event_id' => $event->id,
                'company_id' => $company->id,
            ]);

            return false;
        }
    }

    public function createMeetLinkForWorkspaceEvent(WorkspaceCalendarEvent $event): ?string
    {
        if (! $this->syncWorkspaceEvent($event, true)) {
            return null;
        }

        $event->refresh();

        return filled($event->google_meet_url) ? (string) $event->google_meet_url : null;
    }

    private function workspaceEventDescription(WorkspaceCalendarEvent $event): string
    {
        $lines = [];
        if (filled($event->description)) {
            $lines[] = (string) $event->description;
        }
        if (filled($event->meeting_url)) {
            $lines[] = __('calendar_meeting_link_label').': '.$event->meeting_url;
        }

        return trim(implode("\n", $lines));
    }

    private function extractMeetUrl(Event $googleEvent): ?string
    {
        $entryPoints = $googleEvent->getConferenceData()?->getEntryPoints() ?? [];
        foreach ($entryPoints as $entryPoint) {
            if ($entryPoint->getEntryPointType() === 'video' && filled($entryPoint->getUri())) {
                return (string) $entryPoint->getUri();
            }
        }

        return null;
    }

    /**
     * One-way export for aggregated calendar items (invoices, tasks, etc.).
     */
    public function exportAllDayEvent(Company $company, string $summary, string $description, string $startDate): bool
    {
        $client = $this->makeClient($company);
        if (! $client) {
            return false;
        }

        $cal = new Calendar($client);
        $tz = (string) config('app.timezone', 'UTC');
        $endDate = Carbon::parse($startDate, $tz)->addDay()->toDateString();

        $googleEvent = new Event;
        $googleEvent->setSummary(mb_substr($summary, 0, 200));
        if ($description !== '') {
            $googleEvent->setDescription($description);
        }

        $start = new EventDateTime;
        $start->setDate($startDate);
        $googleEvent->setStart($start);

        $end = new EventDateTime;
        $end->setDate($endDate);
        $googleEvent->setEnd($end);

        try {
            $cal->events->insert('primary', $googleEvent, ['sendUpdates' => 'none']);

            return true;
        } catch (Throwable $e) {
            Log::warning('google.calendar.export', [
                'message' => $e->getMessage(),
                'company_id' => $company->id,
            ]);

            return false;
        }
    }

    public function syncByCompositeEventId(Company $company, string $compositeId, string $title, string $date, ?string $subtitle = null): bool
    {
        if (str_starts_with($compositeId, 'custom-')) {
            $event = WorkspaceCalendarEvent::query()
                ->where('company_id', $company->id)
                ->find(substr($compositeId, 7));

            return $event instanceof WorkspaceCalendarEvent
                && $this->syncWorkspaceEvent($event);
        }

        if (preg_match('/^project-(?:created|deadline)-(.+)$/', $compositeId, $matches) === 1) {
            $project = Project::query()->where('company_id', $company->id)->find($matches[1]);
            if (! $project instanceof Project) {
                return false;
            }
            $this->syncProject($project);

            return true;
        }

        $description = trim(($subtitle ?? '')."\n".url('/calendar'), "\n");

        return $this->exportAllDayEvent($company, $title, $description, $date);
    }
}
