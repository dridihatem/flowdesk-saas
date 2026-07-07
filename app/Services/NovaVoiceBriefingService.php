<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Enums\SupportTicketStatus;
use App\Enums\TaskStatus;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkspaceCalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NovaVoiceBriefingService
{
    public function __construct(
        private DashboardMetricsService $dashboardMetrics,
        private NovaAssistantService $assistant,
    ) {}

    /**
     * @return list<string>
     */
    public function briefingPhrases(): array
    {
        $all = trans('nova_briefing.phrases');

        if (! is_array($all)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($p) => $this->normalizePhrase((string) $p),
            $all,
        ))));
    }

    /**
     * @return array{text: string, paragraphs: list<string>}
     */
    public function buildBriefing(Company $company, User $user): array
    {
        $data = $this->gatherData($company, $user);
        $paragraphs = $this->buildParagraphs($data);
        $text = implode("\n\n", $paragraphs);

        return [
            'text' => $text,
            'paragraphs' => $paragraphs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherData(Company $company, User $user): array
    {
        $cid = $company->id;
        $metrics = $this->dashboardMetrics->forCompany($company);
        $summary = $this->assistant->summaryMetrics($company);
        $currency = flowdesk_normalize_currency_code($summary['currency'] ?? $company->default_currency ?? 'USD');

        $firstName = trim(explode(' ', trim((string) $user->name))[0] ?? '');

        $pendingProjectsQuery = Project::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', ProjectStatus::Pending->value);

        $pendingProjectsCount = (clone $pendingProjectsQuery)->count();

        $pendingProjects = (clone $pendingProjectsQuery)
            ->latest('updated_at')
            ->limit(5)
            ->get(['title']);

        $staleProjects = Project::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->whereIn('status', [ProjectStatus::InProgress->value, ProjectStatus::Approved->value])
            ->where('updated_at', '<', now()->subDays(14))
            ->count();

        $overdueInvoices = $this->countOverdueInvoices($cid);

        $openTasks = ProjectTask::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', '!=', TaskStatus::Done->value)
            ->count();

        $overdueTasks = ProjectTask::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', '!=', TaskStatus::Done->value)
            ->whereNotNull('ends_on')
            ->where('ends_on', '<', now()->toDateString())
            ->count();

        $today = now()->toDateString();
        $weekEnd = now()->addDays(7)->toDateString();

        $upcomingEvents = WorkspaceCalendarEvent::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->whereBetween('starts_on', [$today, $weekEnd])
            ->orderBy('starts_on')
            ->limit(6)
            ->get(['title', 'starts_on']);

        $openInquiries = Inquiry::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->whereIn('status', [InquiryStatus::New->value, InquiryStatus::InProgress->value])
            ->count();

        $openTickets = SupportTicket::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->whereIn('status', [SupportTicketStatus::Open->value, SupportTicketStatus::InProgress->value])
            ->count();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonthRevenue = (int) Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $lastMonthRevenue = (int) Payment::query()
            ->withoutGlobalScopes()
            ->where('company_id', $cid)
            ->where('status', PaymentStatus::Completed)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        return [
            'first_name' => $firstName,
            'company_name' => trim((string) $company->name),
            'currency' => $currency,
            'clients_count' => (int) ($metrics['clients_count'] ?? 0),
            'active_projects' => (int) ($summary['active_projects'] ?? 0),
            'pending_projects' => $pendingProjects->pluck('title')->filter()->values()->all(),
            'pending_projects_count' => $pendingProjectsCount,
            'stale_projects' => $staleProjects,
            'monthly_revenue_formatted' => $summary['monthly_revenue_formatted'],
            'growth_percent' => $summary['growth_percent'],
            'unpaid_invoices' => (int) ($summary['unpaid_invoices'] ?? 0),
            'overdue_invoices' => $overdueInvoices,
            'outstanding_formatted' => flowdesk_format_minor((int) ($metrics['outstanding_amount_minor'] ?? 0), $currency).' '.$currency,
            'open_tasks' => $openTasks,
            'overdue_tasks' => $overdueTasks,
            'upcoming_events' => $upcomingEvents->map(fn ($e) => [
                'title' => (string) $e->title,
                'date' => $this->formatEventDate($e->starts_on),
            ])->all(),
            'open_inquiries' => $openInquiries,
            'open_tickets' => $openTickets,
            'this_month_revenue' => $thisMonthRevenue,
            'last_month_revenue' => $lastMonthRevenue,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function buildParagraphs(array $data): array
    {
        $paragraphs = [];

        $paragraphs[] = __('nova_briefing.intro', [
            'name' => $data['first_name'] !== '' ? $data['first_name'] : __('nova_briefing.guest'),
            'company' => $data['company_name'] !== '' ? $data['company_name'] : config('app.name'),
        ]);

        if (($data['this_month_revenue'] ?? 0) > 0 || ($data['last_month_revenue'] ?? 0) > 0) {
            $growth = $data['growth_percent'];
            if ($growth === null) {
                $paragraphs[] = __('nova_briefing.revenue_no_compare', [
                    'amount' => $data['monthly_revenue_formatted'],
                ]);
            } elseif ($growth >= 0) {
                $paragraphs[] = __('nova_briefing.revenue_up', [
                    'amount' => $data['monthly_revenue_formatted'],
                    'percent' => abs((float) $growth),
                ]);
            } else {
                $paragraphs[] = __('nova_briefing.revenue_down', [
                    'amount' => $data['monthly_revenue_formatted'],
                    'percent' => abs((float) $growth),
                ]);
            }
        } else {
            $paragraphs[] = __('nova_briefing.revenue_none');
        }

        $paragraphs[] = __('nova_briefing.clients_projects', [
            'clients' => $data['clients_count'],
            'projects' => $data['active_projects'],
        ]);

        if (($data['pending_projects_count'] ?? 0) > 0) {
            $titles = implode(', ', array_slice($data['pending_projects'], 0, 3));
            $paragraphs[] = __('nova_briefing.pending_projects', [
                'count' => $data['pending_projects_count'],
                'titles' => $titles,
            ]);
        }

        if (($data['stale_projects'] ?? 0) > 0) {
            $paragraphs[] = __('nova_briefing.stale_projects', [
                'count' => $data['stale_projects'],
            ]);
        }

        if (($data['unpaid_invoices'] ?? 0) > 0) {
            $paragraphs[] = __('nova_briefing.unpaid_invoices', [
                'count' => $data['unpaid_invoices'],
                'amount' => $data['outstanding_formatted'],
            ]);

            if (($data['overdue_invoices'] ?? 0) > 0) {
                $paragraphs[] = __('nova_briefing.overdue_invoices', [
                    'count' => $data['overdue_invoices'],
                ]);
            }
        } else {
            $paragraphs[] = __('nova_briefing.invoices_clear');
        }

        if (($data['open_tasks'] ?? 0) > 0) {
            if (($data['overdue_tasks'] ?? 0) > 0) {
                $paragraphs[] = __('nova_briefing.tasks_overdue', [
                    'open' => $data['open_tasks'],
                    'overdue' => $data['overdue_tasks'],
                ]);
            } else {
                $paragraphs[] = __('nova_briefing.tasks_open', [
                    'count' => $data['open_tasks'],
                ]);
            }
        }

        $events = $data['upcoming_events'] ?? [];
        if ($events !== []) {
            $lines = collect($events)->take(4)->map(
                fn ($e) => __('nova_briefing.event_item', ['title' => $e['title'], 'date' => $e['date']])
            )->implode('. ');
            $paragraphs[] = __('nova_briefing.events_intro').' '.$lines.'.';
        } else {
            $paragraphs[] = __('nova_briefing.events_none');
        }

        $activityParts = [];
        if (($data['open_inquiries'] ?? 0) > 0) {
            $activityParts[] = __('nova_briefing.inquiries_open', ['count' => $data['open_inquiries']]);
        }
        if (($data['open_tickets'] ?? 0) > 0) {
            $activityParts[] = __('nova_briefing.tickets_open', ['count' => $data['open_tickets']]);
        }
        if ($activityParts !== []) {
            $paragraphs[] = implode(' ', $activityParts);
        }

        $paragraphs[] = __('nova_briefing.outro');

        return array_values(array_filter(array_map('trim', $paragraphs)));
    }

    private function countOverdueInvoices(string|int $companyId): int
    {
        $today = now()->toDateString();

        return Invoice::query()
            ->withoutGlobalScope('tenant')
            ->where('company_id', $companyId)
            ->where('status', '!=', InvoiceStatus::Cancelled)
            ->where('amount', '>', 0)
            ->where(function ($q) use ($today) {
                $q->where('status', InvoiceStatus::Overdue->value)
                    ->orWhere(function ($q2) use ($today) {
                        $q2->whereNotNull('due_date')
                            ->where('due_date', '<', $today)
                            ->whereNotIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value]);
                    });
            })
            ->withSum(
                ['payments as completed_payments_sum' => fn ($query) => $query->where('status', PaymentStatus::Completed)],
                'amount'
            )
            ->get(['amount', 'status'])
            ->filter(function ($invoice) {
                $paidMinor = (int) ($invoice->completed_payments_sum ?? 0);

                return max(0, (int) $invoice->amount - $paidMinor) > 0;
            })
            ->count();
    }

    private function formatEventDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        if ($date->isToday()) {
            return __('nova_briefing.today');
        }

        if ($date->isTomorrow()) {
            return __('nova_briefing.tomorrow');
        }

        return $date->translatedFormat('l j F');
    }

    private function normalizePhrase(string $phrase): string
    {
        $s = Str::ascii(mb_strtolower(trim($phrase)));
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s);
    }
}
