<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use App\Models\Provider;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ClientFollowUpService
{
    public function __construct(
        private WorkspaceCalendarService $calendar,
        private CalendarMeetingLinkService $meetings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function showPayload(Client $client, Company $company): array
    {
        $client->loadMissing(['user']);

        $invoices = $client->invoices()
            ->with(['payments' => fn ($q) => $q->latest('paid_at')])
            ->latest()
            ->limit(25)
            ->get();

        $proposals = $client->proposals()->latest()->limit(25)->get();

        $payments = Payment::query()
            ->where('company_id', $company->id)
            ->whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))
            ->with('invoice:id,number,currency')
            ->latest('paid_at')
            ->limit(25)
            ->get();

        $meetings = $client->calendarEvents()
            ->whereIn('kind', ['meeting', 'appointment'])
            ->orderByDesc('starts_on')
            ->limit(20)
            ->get()
            ->map(fn (WorkspaceCalendarEvent $event) => $this->formatMeeting($event));

        $reminders = $client->calendarEvents()
            ->where('kind', 'reminder')
            ->orderByDesc('starts_on')
            ->limit(15)
            ->get();

        $feedbacks = $client->feedbacks()
            ->with(['author:id,name', 'provider:id,name'])
            ->limit(20)
            ->get();

        $notes = $client->notes()
            ->with(['author:id,name', 'provider:id,name'])
            ->limit(50)
            ->get();

        $tasks = $this->tasksForClient($client, $company);

        $projects = $client->projects()
            ->orderBy('title')
            ->get(['id', 'title']);

        $providers = Provider::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $timeline = $this->buildTimeline($client, $company);

        $from = Carbon::today()->subDays(30);
        $to = Carbon::today()->addDays(120);
        $calendarEvents = $this->calendar->eventsForCompany($company, $from, $to, (string) $client->id);

        $unpaidInvoices = $client->invoices()
            ->whereNotIn('status', [InvoiceStatus::Paid, InvoiceStatus::Cancelled])
            ->count();

        $totalPaidMinor = (int) Payment::query()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))
            ->sum('amount');

        $googleConnected = app(GoogleCalendarSyncService::class)->isConnected($company);

        return [
            'invoices' => $invoices,
            'proposals' => $proposals,
            'payments' => $payments,
            'meetings' => $meetings,
            'reminders' => $reminders,
            'feedbacks' => $feedbacks,
            'notes' => $notes,
            'tasks' => $tasks,
            'projects' => $projects,
            'providers' => $providers,
            'timeline' => $timeline,
            'calendarEvents' => $calendarEvents,
            'stats' => [
                'invoices_count' => $client->invoices()->count(),
                'proposals_count' => $client->proposals()->count(),
                'projects_count' => $client->projects()->count(),
                'unpaid_invoices' => $unpaidInvoices,
                'total_paid_minor' => $totalPaidMinor,
            ],
            'googleConnected' => $googleConnected,
            'chatUrl' => route('chat.open.client', $client),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatMeeting(WorkspaceCalendarEvent $event): array
    {
        $meetingUrl = $this->meetings->publicMeetingUrl($event);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'date' => $event->starts_on->toDateString(),
            'start_time' => $event->start_time,
            'meeting_url' => $meetingUrl,
            'meeting_link_type' => $event->meeting_link_type?->value ?? $event->meeting_link_type,
            'meeting_summary' => $event->meeting_summary,
            'google_synced' => filled($event->google_calendar_event_id),
            'invite_sent_at' => $event->invite_sent_at?->toIso8601String(),
        ];
    }

    /**
     * @return Collection<int, ProjectTask>
     */
    public function tasksForClient(Client $client, Company $company): Collection
    {
        return ProjectTask::query()
            ->where('company_id', $company->id)
            ->whereHas('project', fn ($q) => $q->where('client_id', $client->id))
            ->with([
                'project:id,title',
                'comments' => fn ($q) => $q->with('user:id,name')->orderBy('created_at'),
            ])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * @return list<array{type: string, at: string, title: string, body: ?string, meta: ?string, url: ?string}>
     */
    public function buildTimeline(Client $client, Company $company): array
    {
        $events = [];

        if ($client->created_at) {
            $events[] = $this->timelineEntry(
                'client_created',
                $client->created_at,
                __('client_timeline_created'),
                null,
                null,
                null,
            );
        }

        $client->notes()
            ->with(['author:id,name', 'provider:id,name'])
            ->limit(50)
            ->get()
            ->each(function ($note) use (&$events, $company): void {
                $at = Carbon::parse($note->noted_on->toDateString());
                if ($note->start_time) {
                    $at = Carbon::parse($note->noted_on->toDateString().' '.$note->start_time);
                }

                $events[] = $this->timelineEntry(
                    'note_'.$note->note_type?->value,
                    $at,
                    $note->title ?: $note->note_type?->label() ?? __('Notes'),
                    $note->authorLabel($company->name).' · '.$note->note_type?->label(),
                    \Illuminate\Support\Str::limit($note->body, 160),
                    null,
                );
            });

        foreach ($client->proposals()->latest()->limit(30)->get() as $proposal) {
            $events[] = $this->timelineEntry(
                'proposal',
                $proposal->created_at ?? now(),
                __('client_timeline_proposal', ['name' => $proposal->name]),
                $proposal->status->label(),
                $proposal->number,
                route('proposals.show', $proposal),
            );
        }

        foreach ($client->invoices()->latest()->limit(30)->get() as $invoice) {
            $events[] = $this->timelineEntry(
                'invoice',
                $invoice->created_at ?? now(),
                __('client_timeline_invoice', ['number' => $invoice->number ?? '—']),
                $invoice->status->label(),
                null,
                route('invoices.show', $invoice),
            );
        }

        Payment::query()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->whereHas('invoice', fn ($q) => $q->where('client_id', $client->id))
            ->with('invoice:id,number')
            ->latest('paid_at')
            ->limit(30)
            ->get()
            ->each(function (Payment $payment) use (&$events): void {
                $currency = strtoupper((string) ($payment->invoice?->currency ?? 'USD'));
                $events[] = $this->timelineEntry(
                    'payment',
                    $payment->paid_at ?? $payment->created_at ?? now(),
                    __('client_timeline_payment'),
                    flowdesk_format_minor((int) $payment->amount, $currency).' '.$currency,
                    $payment->invoice?->number,
                    $payment->invoice ? route('invoices.show', $payment->invoice) : null,
                );
            });

        Inquiry::query()
            ->where('company_id', $company->id)
            ->where('client_id', $client->id)
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (Inquiry $inquiry) use (&$events): void {
                $events[] = $this->timelineEntry(
                    'inquiry',
                    $inquiry->created_at ?? now(),
                    __('client_timeline_inquiry', ['subject' => $inquiry->subject ?? __('Inquiry')]),
                    __('inquiry_status.'.$inquiry->status->value),
                    null,
                    null,
                );
            });

        $client->calendarEvents()
            ->latest('starts_on')
            ->limit(40)
            ->get()
            ->each(function (WorkspaceCalendarEvent $event) use (&$events): void {
                $at = $event->starts_on?->startOfDay();
                if ($event->start_time) {
                    $at = Carbon::parse($event->starts_on->toDateString().' '.$event->start_time);
                }

                $type = $event->kind === 'reminder' ? 'reminder' : 'meeting';
                $events[] = $this->timelineEntry(
                    $type,
                    $at ?? $event->created_at ?? now(),
                    $event->title,
                    $event->kind === 'meeting' ? __('Meetings') : __('Reminders'),
                    $event->meeting_summary ? \Illuminate\Support\Str::limit($event->meeting_summary, 120) : $event->description,
                    null,
                );
            });

        $client->feedbacks()
            ->with(['author:id,name', 'provider:id,name'])
            ->limit(40)
            ->get()
            ->each(function ($feedback) use (&$events): void {
                $author = $feedback->kind?->value === 'provider'
                    ? ($feedback->provider?->name ?? __('Provider'))
                    : ($feedback->author?->name ?? __('Team'));

                $events[] = $this->timelineEntry(
                    $feedback->kind?->value === 'provider' ? 'feedback_provider' : 'feedback_team',
                    $feedback->created_at ?? now(),
                    $feedback->kind?->value === 'provider'
                        ? __('client_timeline_provider_feedback', ['name' => $author])
                        : __('client_timeline_team_feedback', ['name' => $author]),
                    $feedback->rating ? $feedback->rating.'/5' : null,
                    \Illuminate\Support\Str::limit($feedback->body, 160),
                    null,
                );
            });

        ProjectTask::query()
            ->where('company_id', $company->id)
            ->whereHas('project', fn ($q) => $q->where('client_id', $client->id))
            ->with('project:id,name')
            ->latest()
            ->limit(40)
            ->get()
            ->each(function (ProjectTask $task) use (&$events): void {
                $events[] = $this->timelineEntry(
                    'task',
                    $task->created_at ?? now(),
                    __('client_timeline_task', ['title' => $task->title, 'project' => $task->project?->name ?? '—']),
                    $task->status->label(),
                    null,
                    $task->project ? route('projects.show', [$task->project, 'tab' => 'tasks']) : null,
                );
            });

        ProjectTaskComment::query()
            ->where('company_id', $company->id)
            ->whereHas('task.project', fn ($q) => $q->where('client_id', $client->id))
            ->with(['user:id,name', 'task:id,title,project_id', 'task.project:id,name'])
            ->latest()
            ->limit(40)
            ->get()
            ->each(function (ProjectTaskComment $comment) use (&$events): void {
                $events[] = $this->timelineEntry(
                    'task_comment',
                    $comment->created_at ?? now(),
                    __('client_timeline_task_comment', [
                        'task' => $comment->task?->title ?? '—',
                        'author' => $comment->user?->name ?? ($comment->is_client ? __('Client') : __('Team')),
                    ]),
                    $comment->task?->project?->name,
                    \Illuminate\Support\Str::limit($comment->body, 160),
                    ($comment->task && $comment->task->project)
                        ? route('projects.show', [$comment->task->project, 'tab' => 'tasks'])
                        : null,
                );
            });

        usort($events, fn (array $a, array $b): int => strcmp($b['at'], $a['at']));

        return $events;
    }

    /**
     * @return array{type: string, at: string, title: string, body: ?string, meta: ?string, url: ?string}
     */
    private function timelineEntry(
        string $type,
        Carbon $at,
        string $title,
        ?string $meta,
        ?string $body,
        ?string $url,
    ): array {
        return [
            'type' => $type,
            'at' => $at->toIso8601String(),
            'title' => $title,
            'meta' => $meta,
            'body' => $body,
            'url' => $url,
        ];
    }
}
