<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Proposal;
use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NovaAssistantService
{
    public function __construct(
        private DashboardMetricsService $dashboardMetrics,
        private AnalyticsService $analytics,
        private PlatformLlmRouter $llm,
        private NovaQuestionIntentService $intent,
        private NovaWebSearchService $webSearch,
    ) {}

    public function assistantName(?Company $company = null): string
    {
        $base = $company?->name ?: config('app.name');

        $brand = (string) config('flowdesk.ai_assistant_brand_name', 'Nova');

        return trim($base).' '.$brand;
    }

    /**
     * @return array{
     *   assistant_name: string,
     *   clients_count: int,
     *   active_projects: int,
     *   monthly_revenue_minor: int,
     *   monthly_revenue_formatted: string,
     *   currency: string,
     *   unpaid_invoices: int,
     *   growth_percent: ?float,
     *   recommendations: list<string>
     * }
     */
    public function summaryMetrics(Company $company): array
    {
        $metrics = $this->dashboardMetrics->forCompany($company);
        $currency = flowdesk_normalize_currency_code($metrics['currency'] ?? $company->default_currency ?? 'USD');

        $activeProjects = Project::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [
                ProjectStatus::InProgress->value,
                ProjectStatus::Approved->value,
                ProjectStatus::Pending->value,
            ])
            ->count();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonthRevenue = (int) Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $lastMonthRevenue = (int) Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        $growthPercent = null;
        if ($lastMonthRevenue > 0) {
            $growthPercent = round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1);
        } elseif ($thisMonthRevenue > 0) {
            $growthPercent = 100.0;
        }

        $summary = [
            'assistant_name' => $this->assistantName($company),
            'clients_count' => (int) ($metrics['clients_count'] ?? 0),
            'active_projects' => $activeProjects,
            'monthly_revenue_minor' => $thisMonthRevenue,
            'monthly_revenue_formatted' => flowdesk_format_minor($thisMonthRevenue, $currency).' '.$currency,
            'currency' => $currency,
            'unpaid_invoices' => (int) ($metrics['open_invoices_count'] ?? 0),
            'growth_percent' => $growthPercent,
            'recommendations' => [],
        ];

        $summary['recommendations'] = $this->ruleBasedRecommendations($company, $summary);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return list<string>
     */
    public function ruleBasedRecommendations(Company $company, ?array $summary = null): array
    {
        $summary ??= $this->summaryMetrics($company);
        $tips = [];

        if (($summary['unpaid_invoices'] ?? 0) > 0) {
            $tips[] = __('nova_rec_unpaid_invoices', ['count' => $summary['unpaid_invoices']]);
        }

        if (($summary['growth_percent'] ?? 0) < 0) {
            $tips[] = __('nova_rec_revenue_down');
        } elseif (($summary['growth_percent'] ?? 0) > 10) {
            $tips[] = __('nova_rec_revenue_up');
        }

        $staleProjects = Project::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereIn('status', [ProjectStatus::InProgress->value, ProjectStatus::Approved->value])
            ->where('updated_at', '<', now()->subDays(14))
            ->count();

        if ($staleProjects > 0) {
            $tips[] = __('nova_rec_stale_projects', ['count' => $staleProjects]);
        }

        if ($tips === []) {
            $tips[] = __('nova_rec_default');
        }

        return array_slice($tips, 0, 4);
    }

    public function buildCompanyContext(Company $company): string
    {
        $cid = $company->id;
        $currency = flowdesk_normalize_currency_code($company->default_currency ?? 'USD');
        $metrics = $this->dashboardMetrics->forCompany($company);
        $summary = $this->summaryMetrics($company);

        $lines = [
            'Company: '.$company->name,
            'Default currency: '.$currency,
            'Clients: '.($metrics['clients_count'] ?? 0),
            'Projects (all): '.($metrics['projects_count'] ?? 0),
            'Active projects: '.$summary['active_projects'],
            'Open / unpaid invoices: '.($metrics['open_invoices_count'] ?? 0),
            'Paid invoices: '.($metrics['paid_invoices_count'] ?? 0),
            'Outstanding balance ('.$currency.'): '.flowdesk_format_minor((int) ($metrics['outstanding_amount_minor'] ?? 0), $currency),
            'Revenue this month ('.$currency.'): '.$summary['monthly_revenue_formatted'],
            'Revenue growth vs last month: '.($summary['growth_percent'] !== null ? $summary['growth_percent'].'%' : 'n/a'),
            'Team users: '.User::query()->where('company_id', $cid)->count(),
            '',
            'Top clients by project count:',
        ];

        $topClients = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->withCount('projects')
            ->orderByDesc('projects_count')
            ->limit(8)
            ->get(['id', 'name']);

        foreach ($topClients as $client) {
            $lines[] = '- '.$client->name.' ('.$client->projects_count.' projects)';
        }

        $lines[] = '';
        $lines[] = 'Recent active projects:';
        $projects = Project::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->with('client:id,name')
            ->whereNotIn('status', [ProjectStatus::Completed->value])
            ->latest('updated_at')
            ->limit(10)
            ->get(['id', 'title', 'status', 'client_id', 'final_deadline', 'updated_at']);

        foreach ($projects as $project) {
            $lines[] = sprintf(
                '- %s | %s | client: %s | deadline: %s',
                $project->title,
                $this->enumLabel($project->status),
                $project->client?->name ?? '—',
                $project->final_deadline?->toDateString() ?? '—'
            );
        }

        $lines[] = '';
        $lines[] = 'Open tasks (sample):';
        $tasks = ProjectTask::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', '!=', TaskStatus::Done->value)
            ->with('project:id,title')
            ->latest('updated_at')
            ->limit(12)
            ->get(['id', 'title', 'status', 'project_id', 'ends_on']);

        foreach ($tasks as $task) {
            $lines[] = sprintf(
                '- %s | %s | project: %s | due: %s',
                $task->title,
                $this->enumLabel($task->status),
                $task->project?->title ?? '—',
                $task->ends_on?->toDateString() ?? '—'
            );
        }

        $lines[] = '';
        $lines[] = 'Recent invoices:';
        $invoices = Invoice::query()
            ->withoutGlobalScope('tenant')
            ->where('company_id', $cid)
            ->with('client:id,name')
            ->latest()
            ->limit(10)
            ->get(['id', 'status', 'amount', 'currency', 'client_id', 'due_date']);

        foreach ($invoices as $invoice) {
            $invCurrency = flowdesk_invoice_currency($invoice);
            $lines[] = sprintf(
                '- %s | %s | %s %s | client: %s | due: %s',
                $invoice->id,
                $this->enumLabel($invoice->status),
                flowdesk_format_minor((int) $invoice->amount, $invCurrency),
                $invCurrency,
                $invoice->client?->name ?? '—',
                $invoice->due_date?->toDateString() ?? '—'
            );
        }

        $lines[] = '';
        $lines[] = 'Recent completed payments (revenue):';
        $payments = Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', PaymentStatus::Completed)
            ->latest()
            ->limit(8)
            ->get(['amount', 'currency', 'provider', 'created_at']);

        foreach ($payments as $payment) {
            $payCurrency = flowdesk_normalize_currency_code($payment->currency ?? $currency);
            $lines[] = sprintf(
                '- %s %s via %s on %s',
                flowdesk_format_minor((int) $payment->amount, $payCurrency),
                $payCurrency,
                $payment->provider ?? 'manual',
                $payment->created_at?->toDateString() ?? '—'
            );
        }

        $lines[] = '';
        $lines[] = 'Upcoming calendar / bookings:';
        $events = WorkspaceCalendarEvent::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('starts_on', '>=', now()->toDateString())
            ->orderBy('starts_on')
            ->limit(8)
            ->get(['title', 'kind', 'starts_on']);

        if ($events->isEmpty()) {
            $lines[] = '- (none scheduled)';
        } else {
            foreach ($events as $event) {
                $lines[] = sprintf(
                    '- %s | %s | %s',
                    $event->title,
                    $event->kind,
                    $event->starts_on?->toDateString() ?? '—'
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{
     *   reply: string,
     *   conversation_id: string,
     *   model: string,
     *   input_tokens: int,
     *   output_tokens: int,
     *   total_tokens: int
     * }
     */
    public function chat(Company $company, User $user, string $message, ?string $conversationId = null): array
    {
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException(__('nova_empty_message'));
        }

        $conversation = $this->resolveConversation($company, $user, $conversationId, $message);
        $history = $conversation->messages()->latest()->limit(12)->get()->reverse()->values();

        $analysis = $this->intent->analyze($company, $message);
        $isGeneral = $analysis['intent'] === 'general';

        $system = $isGeneral
            ? $this->generalSystemPrompt($company)
            : $this->workspaceSystemPrompt($company);

        $userPrompt = $isGeneral
            ? $this->buildGeneralUserPrompt($history, $message)
            : $this->buildWorkspaceUserPrompt($company, $history, $message, $analysis);

        if ($isGeneral) {
            $snippets = $this->webSearch->searchSnippets($message);
            $result = $this->llm->completeWithWebAwareness($system, $userPrompt, $snippets, 2048, $company);
        } else {
            $result = $this->llm->complete($system, $userPrompt, 2048, $company);
            $result['used_web_search'] = false;
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['suggestion'],
        ]);

        if (! $conversation->title) {
            $conversation->update([
                'title' => Str::limit($message, 80),
            ]);
        }

        $conversation->touch();

        return [
            'reply' => $result['suggestion'],
            'conversation_id' => $conversation->id,
            'model' => $result['model'],
            'input_tokens' => (int) ($result['input_tokens'] ?? 0),
            'output_tokens' => (int) ($result['output_tokens'] ?? 0),
            'total_tokens' => (int) ($result['total_tokens'] ?? 0),
            'intent' => $analysis['intent'],
            'used_web_search' => (bool) ($result['used_web_search'] ?? false),
        ];
    }

    /**
     * @return Collection<int, AiConversation>
     */
    public function recentConversations(Company $company, User $user, int $limit = 8): Collection
    {
        return AiConversation::query()
            ->where('company_id', $company->id)
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    private function workspaceSystemPrompt(Company $company): string
    {
        $name = $this->assistantName($company);

        return 'You are '.$name.', an intelligent business assistant for '.$company->name.'. '
            .'Answer questions about THIS workspace only: clients, projects, invoices, quotes, revenue, payments, tasks, calendar, and team activity. '
            .'Use the workspace data snapshot and any focused project/client sections below — cite real names, amounts, and dates from that data. '
            .'Do not invent figures or records that are not in the provided data. '
            .'If the user asks about a specific project or client that appears in the focused sections, prioritize that context. '
            .'For follow-up questions like "back to the project" or "what about that client", use the recent conversation plus workspace data. '
            .'If data is missing, say what is missing and suggest where to look in FlowDesk (Projects, Invoices, Clients). '
            .'Be concise, professional, and actionable. '
            .AiAssistantPrompts::outputLanguageInstruction();
    }

    private function generalSystemPrompt(Company $company): string
    {
        $name = $this->assistantName($company);

        return 'You are '.$name.', a helpful voice and chat assistant. '
            .'The user asked a general knowledge question (not about their FlowDesk workspace). '
            .'Answer accurately using web search results when provided, or your general knowledge. '
            .'If you are unsure, say so. Mention sources briefly when web snippets include URLs. '
            .'Do not make up workspace business data for '.$company->name.'. '
            .'Keep answers clear and conversational for voice playback. '
            .AiAssistantPrompts::outputLanguageInstruction();
    }

    /**
     * @param  array{intent: string, use_web_search: bool, project_ids: list<string>, client_ids: list<string>}  $analysis
     */
    private function buildWorkspaceUserPrompt(Company $company, Collection $history, string $message, array $analysis): string
    {
        $parts = [
            "=== Workspace data snapshot ===\n".$this->buildCompanyContext($company),
        ];

        foreach ($analysis['project_ids'] as $projectId) {
            $project = Project::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereKey($projectId)
                ->first();
            if ($project) {
                $parts[] = "\n=== Focused project ===\n".$this->buildProjectContext($project);
            }
        }

        foreach ($analysis['client_ids'] as $clientId) {
            $client = Client::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereKey($clientId)
                ->first();
            if ($client) {
                $parts[] = "\n=== Focused client ===\n".$this->buildClientContext($client);
            }
        }

        if ($history->isNotEmpty()) {
            $parts[] = "\n=== Recent conversation ===";
            foreach ($history as $row) {
                $role = $row->role === 'assistant' ? 'Assistant' : 'User';
                $parts[] = $role.': '.$row->content;
            }
        }

        $parts[] = "\n=== New question ===\n".$message;

        return implode("\n", $parts);
    }

    /**
     * @param  Collection<int, AiConversationMessage>  $history
     */
    private function buildGeneralUserPrompt(Collection $history, string $message): string
    {
        $parts = [];

        if ($history->isNotEmpty()) {
            $parts[] = '=== Recent conversation ===';
            foreach ($history as $row) {
                $role = $row->role === 'assistant' ? 'Assistant' : 'User';
                $parts[] = $role.': '.$row->content;
            }
        }

        $parts[] = "\n=== Question ===\n".$message;

        return implode("\n", $parts);
    }

    public function buildProjectContext(Project $project): string
    {
        $project->loadMissing(['client:id,name,email', 'tasks', 'invoices.client:id,name']);

        $currency = flowdesk_normalize_currency_code($project->company?->default_currency ?? 'USD');
        $priceMinor = $project->clientAgreedPriceMinor();
        $lines = [
            'Title: '.$project->title,
            'Status: '.$this->enumLabel($project->status),
            'Client: '.($project->client?->name ?? '—'),
            'Final deadline: '.($project->final_deadline?->toDateString() ?? '—'),
            'Agreed price: '.($priceMinor !== null ? flowdesk_format_minor($priceMinor, $currency).' '.$currency : '—'),
            'Updated: '.($project->updated_at?->toDateTimeString() ?? '—'),
        ];

        if ($project->description) {
            $plain = trim(strip_tags((string) $project->description));
            if ($plain !== '') {
                $lines[] = 'Description: '.Str::limit($plain, 600);
            }
        }

        $lines[] = '';
        $lines[] = 'Tasks:';
        $tasks = $project->tasks->sortBy('sort_order')->take(20);
        if ($tasks->isEmpty()) {
            $lines[] = '- (none)';
        } else {
            foreach ($tasks as $task) {
                $lines[] = sprintf(
                    '- %s | %s | due %s',
                    $task->title,
                    $this->enumLabel($task->status),
                    $task->ends_on?->toDateString() ?? '—'
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Linked invoices:';
        if ($project->invoices->isEmpty()) {
            $lines[] = '- (none)';
        } else {
            foreach ($project->invoices->take(8) as $invoice) {
                $invCurrency = flowdesk_invoice_currency($invoice);
                $lines[] = sprintf(
                    '- %s | %s | %s %s',
                    $invoice->number ?? $invoice->id,
                    $this->enumLabel($invoice->status),
                    flowdesk_format_minor((int) $invoice->amount, $invCurrency),
                    $invCurrency
                );
            }
        }

        return implode("\n", $lines);
    }

    public function buildClientContext(Client $client): string
    {
        $company = $client->company;
        $currency = flowdesk_normalize_currency_code($company?->default_currency ?? 'USD');

        $client->loadCount(['projects', 'proposals', 'invoices', 'calendarEvents', 'notes', 'feedbacks']);
        $client->load([
            'projects' => fn ($q) => $q->withoutGlobalScopes()->latest('updated_at')->limit(8),
            'invoices' => fn ($q) => $q->withoutGlobalScopes()->latest('created_at')->limit(8),
            'calendarEvents' => fn ($q) => $q->withoutGlobalScopes()
                ->whereIn('kind', ['meeting', 'appointment'])
                ->orderByDesc('starts_on')
                ->limit(6),
        ]);

        $lines = [
            'Name: '.$client->name,
            'Status: '.($client->status ? $client->status->label() : '—'),
            'Email: '.($client->email ?? '—'),
            'Phone: '.($client->phone ?? '—'),
            'Client code: '.($client->code ?? '—'),
            'Portal account: '.($client->user_id ? 'yes' : 'no'),
            'Projects count: '.(int) $client->projects_count,
            'Quotes count: '.(int) $client->proposals_count,
            'Invoices count: '.(int) $client->invoices_count,
            'Meetings count: '.(int) $client->calendar_events_count,
            'Notes count: '.(int) $client->notes_count,
            'Feedback count: '.(int) $client->feedbacks_count,
        ];

        $unpaidInvoices = $client->invoices->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue]);
        $outstandingMinor = (int) $unpaidInvoices->sum('amount');
        $lines[] = 'Outstanding invoice balance ('.$currency.'): '.flowdesk_format_minor($outstandingMinor, $currency);

        $lines[] = '';
        $lines[] = 'Recent projects:';
        if ($client->projects->isEmpty()) {
            $lines[] = '- (none)';
        } else {
            foreach ($client->projects as $project) {
                $lines[] = sprintf(
                    '- %s | %s | deadline %s | updated %s',
                    $project->title,
                    $this->enumLabel($project->status),
                    $project->final_deadline?->toDateString() ?? '—',
                    $project->updated_at?->toDateString() ?? '—'
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Recent invoices:';
        if ($client->invoices->isEmpty()) {
            $lines[] = '- (none)';
        } else {
            foreach ($client->invoices as $invoice) {
                $lines[] = sprintf(
                    '- %s | %s | %s %s | due %s',
                    $invoice->number ?? $invoice->id,
                    $this->enumLabel($invoice->status),
                    flowdesk_format_minor((int) $invoice->amount, $invoice->currency ?: $currency),
                    $invoice->currency ?: $currency,
                    $invoice->due_date?->toDateString() ?? '—'
                );
            }
        }

        $proposals = Proposal::query()
            ->withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->latest('updated_at')
            ->limit(5)
            ->get(['name', 'status', 'updated_at']);

        $lines[] = '';
        $lines[] = 'Recent quotes:';
        if ($proposals->isEmpty()) {
            $lines[] = '- (none)';
        } else {
            foreach ($proposals as $proposal) {
                $lines[] = sprintf(
                    '- %s | %s | updated %s',
                    $proposal->name,
                    $this->enumLabel($proposal->status),
                    $proposal->updated_at?->toDateString() ?? '—'
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Meetings / calls:';
        if ($client->calendarEvents->isEmpty()) {
            $lines[] = '- (none)';
        } else {
            foreach ($client->calendarEvents as $event) {
                $hasCall = filled($event->meeting_url)
                    || filled($event->google_meet_url)
                    || filled($event->zoom_meeting_id)
                    || ($event->meeting_link_type && $this->enumLabel($event->meeting_link_type) !== 'none');
                $lines[] = sprintf(
                    '- %s | %s | video call: %s',
                    $event->title,
                    $event->starts_on?->toDateString() ?? '—',
                    $hasCall ? 'yes' : 'no'
                );
            }
        }

        return implode("\n", $lines);
    }

    private function systemPrompt(Company $company): string
    {
        return $this->workspaceSystemPrompt($company);
    }

    /**
     * @param  Collection<int, AiConversationMessage>  $history
     */
    private function buildChatUserPrompt(Company $company, Collection $history, string $message): string
    {
        return $this->buildWorkspaceUserPrompt($company, $history, $message, [
            'intent' => 'workspace',
            'use_web_search' => false,
            'project_ids' => [],
            'client_ids' => [],
        ]);
    }

    private function resolveConversation(Company $company, User $user, ?string $conversationId, string $message): AiConversation
    {
        if ($conversationId) {
            $existing = AiConversation::query()
                ->where('id', $conversationId)
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return AiConversation::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'title' => Str::limit($message, 80),
        ]);
    }

    private function enumLabel(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return (string) $value;
    }
}
