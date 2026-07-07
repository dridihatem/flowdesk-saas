<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NovaClientAnalysisService
{
    public function __construct(
        private NovaQuestionIntentService $intent,
    ) {}

    public function isClientAnalysisRequest(string $message): bool
    {
        $normalized = $this->normalize($message);
        if ($normalized === '') {
            return false;
        }

        $hasAnalyze = (bool) preg_match(
            '/\b(analyze|analyse|analysis|review|summarize|summarise|audit|overview|breakdown|report on)\b/u',
            $normalized,
        );
        $hasClient = (bool) preg_match(
            '/\b(clients?|customers?|clientele|comptes?\s+clients?)\b/u',
            $normalized,
        );

        return $hasAnalyze && $hasClient;
    }

    public function tryReply(Company $company, User $user, string $message): ?string
    {
        if (! $this->isClientAnalysisRequest($message)) {
            return null;
        }

        if (! $user->can('workspace.manage_clients')) {
            return __('nova_client_analysis_no_permission');
        }

        $client = $this->resolveClient($company, $message);

        if ($client) {
            return $this->analyzeClient($company, $client);
        }

        if ($this->requestsNamedClient($message)) {
            return __('nova_client_analysis_client_not_found');
        }

        return $this->analyzeClientsOverview($company);
    }

    public function analyzeClient(Company $company, Client $client): string
    {
        $currency = flowdesk_normalize_currency_code($company->default_currency ?? 'USD');
        $clientId = $client->id;
        $companyId = $company->id;

        $client->loadCount(['projects', 'proposals', 'invoices', 'calendarEvents', 'notes', 'feedbacks']);

        $invoices = Invoice::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'number', 'status', 'amount', 'currency', 'due_date']);

        $invoiceStats = $this->invoiceStats($invoices, $currency);

        $proposals = Proposal::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'name', 'status', 'updated_at']);

        $projects = $client->projects()
            ->withoutGlobalScopes()
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'title', 'status', 'final_deadline', 'updated_at']);

        $meetings = WorkspaceCalendarEvent::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->whereIn('kind', ['meeting', 'appointment'])
            ->orderByDesc('starts_on')
            ->limit(10)
            ->get();

        $today = now()->startOfDay();
        $upcomingMeetings = $meetings->filter(fn (WorkspaceCalendarEvent $e) => $e->starts_on?->gte($today))->values();
        $pastMeetings = $meetings->filter(fn (WorkspaceCalendarEvent $e) => $e->starts_on?->lt($today))->values();

        $paymentsTotal = (int) Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', PaymentStatus::Completed)
            ->whereHas('invoice', fn ($q) => $q->withoutGlobalScopes()->where('client_id', $clientId))
            ->sum('amount');

        $lines = [
            __('nova_client_analysis_client_intro', ['name' => $client->name]),
        ];

        if ($client->status) {
            $lines[] = __('nova_client_analysis_status', ['status' => $client->status->label()]);
        }

        if ($client->email) {
            $lines[] = __('nova_client_analysis_email', ['email' => $client->email]);
        }

        if ($client->phone) {
            $lines[] = __('nova_client_analysis_phone', ['phone' => $client->phone]);
        }

        $lines[] = $client->user_id
            ? __('nova_client_analysis_portal_yes')
            : __('nova_client_analysis_portal_no');

        $lines[] = __('nova_client_analysis_projects_count', ['count' => (int) $client->projects_count]);
        if ($projects->isNotEmpty()) {
            foreach ($projects->take(4) as $project) {
                $lines[] = __('nova_client_analysis_project_line', [
                    'title' => $project->title,
                    'status' => $this->enumLabel($project->status),
                    'deadline' => $project->final_deadline?->toDateString() ?? __('nova_client_analysis_no_deadline'),
                ]);
            }
        } else {
            $lines[] = __('nova_client_analysis_no_projects');
        }

        $lines[] = __('nova_client_analysis_invoices_summary', [
            'total' => (int) $client->invoices_count,
            'paid' => $invoiceStats['paid'],
            'unpaid' => $invoiceStats['unpaid'],
            'overdue' => $invoiceStats['overdue'],
            'outstanding' => flowdesk_format_minor($invoiceStats['outstanding_minor'], $currency).' '.$currency,
        ]);

        if ($invoices->isNotEmpty()) {
            foreach ($invoices->take(4) as $invoice) {
                $lines[] = __('nova_client_analysis_invoice_line', [
                    'number' => $invoice->number ?? $invoice->id,
                    'status' => $this->enumLabel($invoice->status),
                    'amount' => flowdesk_format_minor((int) $invoice->amount, $invoice->currency ?: $currency).' '.($invoice->currency ?: $currency),
                    'due' => $invoice->due_date?->toDateString() ?? '—',
                ]);
            }
        }

        if ($paymentsTotal > 0) {
            $lines[] = __('nova_client_analysis_payments_total', [
                'amount' => flowdesk_format_minor($paymentsTotal, $currency).' '.$currency,
            ]);
        }

        $lines[] = __('nova_client_analysis_quotes_summary', [
            'count' => (int) $client->proposals_count,
        ]);
        if ($proposals->isNotEmpty()) {
            foreach ($proposals->take(3) as $proposal) {
                $lines[] = __('nova_client_analysis_quote_line', [
                    'name' => $proposal->name,
                    'status' => $this->enumLabel($proposal->status),
                ]);
            }
        }

        $lines[] = $this->meetingSummary($upcomingMeetings, $pastMeetings);

        if ((int) $client->notes_count > 0) {
            $lines[] = __('nova_client_analysis_notes_count', ['count' => (int) $client->notes_count]);
        }

        if ((int) $client->feedbacks_count > 0) {
            $lines[] = __('nova_client_analysis_feedback_count', ['count' => (int) $client->feedbacks_count]);
        }

        return implode(' ', array_filter($lines));
    }

    public function analyzeClientsOverview(Company $company): string
    {
        $currency = flowdesk_normalize_currency_code($company->default_currency ?? 'USD');
        $companyId = $company->id;

        $totalClients = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->count();

        if ($totalClients === 0) {
            return __('nova_client_analysis_no_clients');
        }

        $clientsWithUnpaid = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('invoices', fn ($q) => $q->withoutGlobalScopes()
                ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value]))
            ->count();

        $clientsWithMeetings = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('calendarEvents', fn ($q) => $q->withoutGlobalScopes()
                ->whereIn('kind', ['meeting', 'appointment']))
            ->count();

        $topClients = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->withCount(['projects', 'invoices'])
            ->orderByDesc('projects_count')
            ->orderByDesc('invoices_count')
            ->limit(6)
            ->get(['id', 'name']);

        $lines = [
            __('nova_client_analysis_overview_intro', ['count' => $totalClients]),
            __('nova_client_analysis_overview_unpaid_clients', ['count' => $clientsWithUnpaid]),
            __('nova_client_analysis_overview_meeting_clients', ['count' => $clientsWithMeetings]),
        ];

        if ($topClients->isNotEmpty()) {
            $lines[] = __('nova_client_analysis_overview_top');
            foreach ($topClients as $client) {
                $unpaid = Invoice::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('client_id', $client->id)
                    ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value])
                    ->count();

                $nextMeeting = WorkspaceCalendarEvent::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('client_id', $client->id)
                    ->whereIn('kind', ['meeting', 'appointment'])
                    ->where('starts_on', '>=', now()->toDateString())
                    ->orderBy('starts_on')
                    ->first(['starts_on', 'title']);

                $lines[] = __('nova_client_analysis_overview_client_line', [
                    'name' => $client->name,
                    'projects' => (int) $client->projects_count,
                    'invoices' => (int) $client->invoices_count,
                    'unpaid' => $unpaid,
                    'meeting' => $nextMeeting
                        ? __('nova_client_analysis_overview_next_meeting', [
                            'date' => $nextMeeting->starts_on?->toDateString() ?? '—',
                            'title' => $nextMeeting->title,
                        ])
                        : __('nova_client_analysis_overview_no_meeting'),
                ]);
            }
        }

        $lines[] = __('nova_client_analysis_overview_hint');

        return implode(' ', array_filter($lines));
    }

    public function resolveClient(Company $company, string $message): ?Client
    {
        $analysis = $this->intent->analyze($company, $message);
        if ($analysis['client_ids'] !== []) {
            $client = Client::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereKey($analysis['client_ids'][0])
                ->first();

            if ($client) {
                return $client;
            }
        }

        $candidate = $this->extractClientNameCandidate($message);
        if ($candidate === null) {
            return null;
        }

        return $this->findClientByName($company, $candidate);
    }

    public function extractClientNameCandidate(string $message): ?string
    {
        $patterns = [
            '/\b(?:analyze|analyse|analysis|review|summarize|summarise|audit|tell me about|look at|check)\s+(?:the\s+)?(?:client|customer)\s+(?:called|named|name)?\s*(.+)$/iu',
            '/\b(?:client|customer)\s+(?:called|named)\s+(.+)$/iu',
            '/\b(?:analyze|analyse|review)\s+(.+?)\s+(?:client|customer)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, trim($message), $matches) !== 1) {
                continue;
            }

            $name = $this->cleanExtractedName($matches[1]);
            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array{paid: int, unpaid: int, overdue: int, outstanding_minor: int}
     */
    private function invoiceStats(Collection $invoices, string $fallbackCurrency): array
    {
        $paid = 0;
        $unpaid = 0;
        $overdue = 0;
        $outstandingMinor = 0;

        foreach ($invoices as $invoice) {
            $status = $this->enumLabel($invoice->status);
            if ($status === InvoiceStatus::Paid->value) {
                $paid++;
            } elseif (in_array($status, [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value], true)) {
                $unpaid++;
                $outstandingMinor += (int) $invoice->amount;
                if ($status === InvoiceStatus::Overdue->value) {
                    $overdue++;
                }
            }
        }

        return [
            'paid' => $paid,
            'unpaid' => $unpaid,
            'overdue' => $overdue,
            'outstanding_minor' => $outstandingMinor,
        ];
    }

    /**
     * @param  Collection<int, WorkspaceCalendarEvent>  $upcoming
     * @param  Collection<int, WorkspaceCalendarEvent>  $past
     */
    private function meetingSummary(Collection $upcoming, Collection $past): string
    {
        if ($upcoming->isEmpty() && $past->isEmpty()) {
            return __('nova_client_analysis_no_meetings');
        }

        $parts = [];

        if ($upcoming->isNotEmpty()) {
            $parts[] = __('nova_client_analysis_upcoming_meetings', ['count' => $upcoming->count()]);
            $next = $upcoming->sortBy('starts_on')->first();
            if ($next) {
                $parts[] = __('nova_client_analysis_next_meeting', [
                    'title' => $next->title,
                    'date' => $next->starts_on?->toDateString() ?? '—',
                    'call' => $this->hasVideoCall($next)
                        ? __('nova_client_analysis_with_video_call', ['type' => $this->meetingCallLabel($next)])
                        : __('nova_client_analysis_without_video_call'),
                ]);
            }
        } else {
            $parts[] = __('nova_client_analysis_no_upcoming_meetings');
        }

        if ($past->isNotEmpty()) {
            $last = $past->sortByDesc('starts_on')->first();
            $parts[] = __('nova_client_analysis_past_meetings', [
                'count' => $past->count(),
                'last_date' => $last?->starts_on?->toDateString() ?? '—',
            ]);
        }

        return implode(' ', $parts);
    }

    private function hasVideoCall(WorkspaceCalendarEvent $event): bool
    {
        $type = $event->meeting_link_type;
        if ($type instanceof \BackedEnum && $type->value !== 'none') {
            return true;
        }

        return filled($event->meeting_url)
            || filled($event->google_meet_url)
            || filled($event->zoom_meeting_id);
    }

    private function meetingCallLabel(WorkspaceCalendarEvent $event): string
    {
        $type = $event->meeting_link_type;
        if ($type instanceof \BackedEnum && $type->value !== 'none') {
            return method_exists($type, 'label') ? $type->label() : $type->value;
        }

        if (filled($event->google_meet_url)) {
            return __('calendar_meeting_google_meet');
        }
        if (filled($event->zoom_meeting_id)) {
            return __('calendar_meeting_zoom');
        }
        if (filled($event->meeting_url)) {
            return __('calendar_meeting_custom_url');
        }

        return __('calendar_meeting_none');
    }

    private function findClientByName(Company $company, string $candidate): ?Client
    {
        $needle = $this->normalize($candidate);
        if ($needle === '') {
            return null;
        }

        $clients = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->orderByDesc('updated_at')
            ->limit(120)
            ->get(['id', 'name']);

        $exact = $clients->first(fn (Client $c) => $this->normalize((string) $c->name) === $needle);
        if ($exact) {
            return $exact;
        }

        $contains = $clients->filter(fn (Client $c) => str_contains($this->normalize((string) $c->name), $needle))->values();
        if ($contains->count() === 1) {
            return $contains->first();
        }

        $best = null;
        $bestScore = 0;
        foreach ($clients as $client) {
            $score = $this->nameSimilarityScore($needle, $this->normalize((string) $client->name));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $client;
            }
        }

        return $bestScore >= 0.6 ? $best : null;
    }

    private function nameSimilarityScore(string $needle, string $haystack): float
    {
        if ($haystack === '' || $needle === '') {
            return 0.0;
        }

        if (str_contains($haystack, $needle) || str_contains($needle, $haystack)) {
            return 0.9;
        }

        $needleTokens = preg_split('/\s+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hayTokens = preg_split('/\s+/u', $haystack, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($needleTokens === [] || $hayTokens === []) {
            return 0.0;
        }

        $hits = 0;
        foreach ($needleTokens as $token) {
            if (mb_strlen($token) < 3) {
                continue;
            }
            foreach ($hayTokens as $hayToken) {
                if ($token === $hayToken || str_contains($hayToken, $token)) {
                    $hits++;
                    break;
                }
            }
        }

        return $hits / max(count($needleTokens), 1);
    }

    private function requestsNamedClient(string $message): bool
    {
        return $this->extractClientNameCandidate($message) !== null
            || (bool) preg_match('/\b(?:client|customer)\s+(?:called|named)\b/u', $this->normalize($message));
    }

    private function cleanExtractedName(string $raw): ?string
    {
        $name = trim($raw, " \t\n\r\0\x0B.,!?\"'`");
        $name = preg_replace('/\s+(please|thanks|thank you)$/iu', '', $name) ?? $name;
        $name = trim($name);

        if ($name === '' || mb_strlen($name) < 2) {
            return null;
        }

        $blocked = ['clients', 'client', 'customers', 'customer', 'all', 'my', 'the', 'our'];
        if (in_array($this->normalize($name), $blocked, true)) {
            return null;
        }

        return $name;
    }

    private function normalize(string $text): string
    {
        $s = mb_strtolower(trim($text));
        $s = str_replace(['’', '`'], "'", $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return $s;
    }

    private function enumLabel(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return (string) $value;
    }
}
