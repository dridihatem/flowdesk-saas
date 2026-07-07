<?php

namespace App\Http\Controllers;

use App\Enums\CalendarMeetingLinkType;
use App\Enums\ClientFeedbackKind;
use App\Enums\ClientNoteAuthorKind;
use App\Enums\ClientNoteType;
use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Enums\TaskStatus;
use App\Mail\ClientMeetingInviteMail;
use App\Models\Client;
use App\Models\ClientFeedback;
use App\Models\ClientNote;
use App\Models\Company;
use App\Models\Project;
use App\Models\Provider;
use App\Models\WorkspaceCalendarEvent;
use App\Services\AiAssistantPrompts;
use App\Services\AiCreditUsageService;
use App\Services\CalendarMeetingLinkService;
use App\Services\GoogleCalendarSyncService;
use App\Services\PlatformLlmRouter;
use App\Services\PlanLimitService;
use App\Services\WorkspaceAiConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use RuntimeException;

class ClientFollowUpController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(! $request->user()->hasAnyRole(['company_admin', 'team_member']), 403);

            return $next($request);
        });
    }

    public function storeMeeting(
        Request $request,
        Client $client,
        CalendarMeetingLinkService $meetings,
        GoogleCalendarSyncService $google,
    ): RedirectResponse {
        $company = $this->assertClient($request, $client);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'meeting_link_type' => ['nullable', 'string', Rule::enum(CalendarMeetingLinkType::class)],
            'send_invite' => ['sometimes', 'boolean'],
            'sync_google' => ['sometimes', 'boolean'],
        ]);

        $linkType = CalendarMeetingLinkType::tryFrom((string) ($validated['meeting_link_type'] ?? 'google_meet'))
            ?? CalendarMeetingLinkType::GoogleMeet;

        if ($linkType === CalendarMeetingLinkType::GoogleMeet && ! $google->isConnected($company)) {
            $linkType = CalendarMeetingLinkType::None;
        }

        $meetingFields = $meetings->resolveForNewEvent(
            $company,
            $linkType,
            null,
            $validated['title'],
            $validated['date'],
        );

        $event = WorkspaceCalendarEvent::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_on' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'kind' => 'meeting',
            ...$meetingFields,
        ]);

        if ($linkType === CalendarMeetingLinkType::GoogleMeet && $google->isConnected($company)) {
            $meetings->finalizeGoogleMeet($event);
            $event->refresh();
        }

        if ($request->boolean('sync_google') && $google->isConnected($company)) {
            $google->syncWorkspaceEvent($event, $linkType === CalendarMeetingLinkType::GoogleMeet);
            $event->refresh();
        }

        if ($request->boolean('send_invite')) {
            $this->sendInvite($request, $client, $company, $event);
        }

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'meetings'])
            ->with('status', __('client_meeting_scheduled'));
    }

    public function storeReminder(Request $request, Client $client): RedirectResponse
    {
        $company = $this->assertClient($request, $client);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
        ]);

        WorkspaceCalendarEvent::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_on' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'kind' => 'reminder',
            'meeting_link_type' => CalendarMeetingLinkType::None,
        ]);

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'reminders'])
            ->with('status', __('client_reminder_saved'));
    }

    public function updateMeetingSummary(Request $request, Client $client, WorkspaceCalendarEvent $event): RedirectResponse
    {
        $company = $this->assertClient($request, $client);
        $this->assertEvent($company, $client, $event);

        $validated = $request->validate([
            'meeting_summary' => ['nullable', 'string', 'max:20000'],
        ]);

        $event->update([
            'meeting_summary' => $validated['meeting_summary'] ?? null,
        ]);

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'meetings', 'meeting' => $event->id])
            ->with('status', __('client_meeting_summary_saved'));
    }

    public function generateMeetingSummary(
        Request $request,
        Client $client,
        WorkspaceCalendarEvent $event,
        PlanLimitService $planLimits,
        AiCreditUsageService $usage,
        PlatformLlmRouter $llm,
    ): JsonResponse {
        $company = $this->assertClient($request, $client);
        $this->assertEvent($company, $client, $event);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:20000'],
        ]);

        $notes = trim((string) ($validated['notes'] ?? ''));
        $eventDescription = trim((string) ($event->description ?? ''));
        if ($notes === '' && $eventDescription === '') {
            return response()->json([
                'message' => __('client_meeting_ai_needs_notes'),
            ], 422);
        }

        $creditCost = $usage->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'summary');
        $planLimits->assertAllows($company, 'ai_credits', $creditCost);

        if (! $llm->isAvailable($company)) {
            return response()->json([
                'message' => app(WorkspaceAiConfigService::class)->unavailableMessage($company),
            ], 503);
        }

        $system = 'You are an account manager assistant preparing an internal post-meeting summary for a client follow-up CRM. '
            .'Write a concise, professional summary with these sections only: Summary, Client feedback, Decisions, Next steps. '
            .'Use bullets where helpful. Do not invent facts. If details are missing, stay generic and mark assumptions briefly. '
            .AiAssistantPrompts::outputLanguageInstruction();

        $user = "Task: Turn the meeting notes below into an internal follow-up summary after a Google Meet with a client.\n"
            .'Audience: company staff using the client detail page in Flowqil.'."\n"
            .'Client: '.$client->name."\n"
            .'Meeting title: '.(string) $event->title."\n"
            .'Meeting date: '.$event->starts_on->toDateString()."\n"
            .'Existing meeting description: '.($eventDescription !== '' ? $eventDescription : 'None')."\n"
            ."Raw notes from the user:\n".($notes !== '' ? $notes : 'No extra notes provided.')."\n\n"
            .'Return only the final summary text, ready to save into the meeting summary field.';

        try {
            $result = $llm->complete($system, $user, 1400, $company);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        $charged = $usage->recordForTask($company, AiCreditUsageService::TASK_ASSISTANT, 'summary');

        return response()->json([
            'suggestion' => (string) ($result['suggestion'] ?? ''),
            'model' => (string) ($result['model'] ?? ''),
            'ai_credits_charged' => $charged,
            'ai_credits_cost' => $creditCost,
            'disclaimer' => __('AI-generated content — review before sending to clients.'),
        ]);
    }

    public function sendMeetingInvite(
        Request $request,
        Client $client,
        WorkspaceCalendarEvent $event,
        CalendarMeetingLinkService $meetings,
    ): RedirectResponse {
        $company = $this->assertClient($request, $client);
        $this->assertEvent($company, $client, $event);

        $this->sendInvite($request, $client, $company, $event, $meetings);

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'meetings', 'meeting' => $event->id])
            ->with('status', __('client_meeting_invite_sent'));
    }

    public function storeFeedback(Request $request, Client $client): RedirectResponse
    {
        $company = $this->assertClient($request, $client);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'max:10000'],
            'kind' => ['nullable', 'string', Rule::in(ClientFeedbackKind::values())],
            'provider_id' => ['nullable', 'ulid', 'exists:providers,id'],
        ]);

        $kind = ClientFeedbackKind::tryFrom((string) ($validated['kind'] ?? ClientFeedbackKind::Team->value))
            ?? ClientFeedbackKind::Team;

        if ($kind === ClientFeedbackKind::Provider) {
            abort_if(empty($validated['provider_id']), 422, __('client_feedback_provider_required'));
            $provider = Provider::query()
                ->where('company_id', $company->id)
                ->find($validated['provider_id']);
            abort_if(! $provider, 422, __('client_feedback_provider_required'));
        }

        ClientFeedback::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'user_id' => $request->user()->id,
            'kind' => $kind,
            'provider_id' => $kind === ClientFeedbackKind::Provider ? ($validated['provider_id'] ?? null) : null,
            'rating' => $validated['rating'] ?? null,
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'feedback'])
            ->with('status', __('client_feedback_saved'));
    }

    public function storeNote(Request $request, Client $client): RedirectResponse
    {
        $company = $this->assertClient($request, $client);

        $validated = $request->validate([
            'author_kind' => ['required', 'string', Rule::in(ClientNoteAuthorKind::values())],
            'provider_id' => ['nullable', 'ulid', 'exists:providers,id'],
            'note_type' => ['required', 'string', Rule::in(ClientNoteType::values())],
            'title' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'noted_on' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'meeting_url' => ['nullable', 'url', 'max:500'],
            'visible_to_client' => ['sometimes', 'boolean'],
        ]);

        $authorKind = ClientNoteAuthorKind::tryFrom($validated['author_kind']) ?? ClientNoteAuthorKind::Team;

        if ($authorKind === ClientNoteAuthorKind::Provider) {
            abort_if(empty($validated['provider_id']), 422, __('client_note_provider_required'));
            $provider = Provider::query()
                ->where('company_id', $company->id)
                ->find($validated['provider_id']);
            abort_if(! $provider, 422, __('client_note_provider_required'));
        }

        $noteType = ClientNoteType::tryFrom($validated['note_type']) ?? ClientNoteType::General;
        $visibleToClient = $request->boolean('visible_to_client') && $client->user_id !== null;

        ClientNote::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'user_id' => $request->user()->id,
            'author_kind' => $authorKind,
            'provider_id' => $authorKind === ClientNoteAuthorKind::Provider ? $validated['provider_id'] : null,
            'note_type' => $noteType,
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'noted_on' => $validated['noted_on'],
            'start_time' => $validated['start_time'] ?? null,
            'meeting_url' => $noteType === ClientNoteType::Meeting ? ($validated['meeting_url'] ?? null) : null,
            'visible_to_client' => $visibleToClient,
        ]);

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'notes'])
            ->with('status', __('client_note_saved'));
    }

    public function storeTask(Request $request, Client $client): RedirectResponse
    {
        $company = $this->assertClient($request, $client);

        $validated = $request->validate([
            'project_id' => ['required', 'ulid', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $project = Project::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->findOrFail($validated['project_id']);

        $status = $request->enum('status', TaskStatus::class) ?? TaskStatus::Todo;
        $maxOrder = (int) $project->tasks()->where('status', $status)->max('sort_order');

        $project->tasks()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
            'scope' => TaskScope::Core,
            'price_mode' => TaskPriceMode::Bundled,
            'billable' => true,
            'company_id' => $company->id,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('clients.show', [$client, 'tab' => 'tasks'])
            ->with('status', __('client_task_created'));
    }

    private function sendInvite(
        Request $request,
        Client $client,
        Company $company,
        WorkspaceCalendarEvent $event,
        ?CalendarMeetingLinkService $meetings = null,
    ): void {
        $email = trim((string) $client->email);
        abort_if($email === '', 422, __('client_meeting_invite_no_email'));

        $meetings ??= app(CalendarMeetingLinkService::class);
        $meetingUrl = $meetings->publicMeetingUrl($event);

        Mail::to($email)->send(new ClientMeetingInviteMail(
            $client,
            $company,
            $event,
            $meetingUrl,
            (string) $request->user()->name,
        ));

        $event->update(['invite_sent_at' => now()]);
    }

    private function assertClient(Request $request, Client $client): Company
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);
        abort_if((string) $client->company_id !== (string) $company->id, 404);

        return $company;
    }

    private function assertEvent(Company $company, Client $client, WorkspaceCalendarEvent $event): void
    {
        abort_if((string) $event->company_id !== (string) $company->id, 404);
        abort_if((string) $event->client_id !== (string) $client->id, 404);
    }
}
