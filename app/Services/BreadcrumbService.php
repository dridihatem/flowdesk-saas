<?php

namespace App\Services;

use App\Models\ChatThread;
use App\Models\Form;
use App\Models\InstalledModule;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BreadcrumbService
{
    /**
     * @return array{items: list<array{label: string, href: ?string}>, back: ?string}
     */
    public function forRequest(Request $request): array
    {
        $route = $request->route();
        if (! $route || ! Auth::check()) {
            return ['items' => [], 'back' => null];
        }

        $name = $route->getName();
        if ($name === null || $this->shouldSkip($name)) {
            return ['items' => [], 'back' => null];
        }

        return match (true) {
            str_starts_with($name, 'admin.') => $this->admin($name, $route),
            str_starts_with($name, 'provider.') => $this->provider($name, $route),
            default => $this->tenant($name, $route),
        };
    }

    private function shouldSkip(string $name): bool
    {
        return str_starts_with($name, 'webhooks.')
            || $name === 'locale.update'
            || str_starts_with($name, 'sanctum.');
    }

    /**
     * @param  list<array{label: string, href: ?string}>  $segments
     * @return array{items: list<array{label: string, href: ?string}>, back: ?string}
     */
    private function trail(array $segments): array
    {
        if ($segments === []) {
            return ['items' => [], 'back' => null];
        }

        $items = $segments;
        $last = count($items) - 1;
        $items[$last] = ['label' => $items[$last]['label'], 'href' => null];
        $back = $last >= 1 ? ($items[$last - 1]['href'] ?? null) : null;

        return ['items' => $items, 'back' => $back];
    }

    /**
     * @return array{label: string, href: string}
     */
    private function homeCrumb(): array
    {
        $user = Auth::user();
        if ($user && $user->hasRole('client')) {
            return ['label' => __('Portal'), 'href' => route('portal.dashboard')];
        }

        return ['label' => __('Dashboard'), 'href' => route('dashboard')];
    }

    private function admin(string $name, Route $route): array
    {
        $adminHub = ['label' => __('Platform admin'), 'href' => route('admin.dashboard')];

        return match ($name) {
            'admin.dashboard' => $this->trail([['label' => __('Platform admin'), 'href' => $adminHub['href']]]),
            'admin.platform-appearance.edit', 'admin.platform-appearance.update' => $this->trail([
                $adminHub,
                ['label' => __('Workspace theme'), 'href' => route('admin.platform-appearance.edit')],
            ]),
            'admin.companies.index' => $this->trail([$adminHub, ['label' => __('Companies'), 'href' => route('admin.companies.index')]]),
            'admin.companies.show' => $this->trail([
                $adminHub,
                ['label' => __('Companies'), 'href' => route('admin.companies.index')],
                ['label' => (string) ($route->parameter('company')?->name ?? __('Company')), 'href' => null],
            ]),
            'admin.plans.index' => $this->trail([$adminHub, ['label' => __('Plans & features'), 'href' => route('admin.plans.index')]]),
            'admin.plans.edit', 'admin.plans.update', 'admin.plans.limits.update' => $this->trail([
                $adminHub,
                ['label' => __('Plans & features'), 'href' => route('admin.plans.index')],
                ['label' => (string) ($route->parameter('plan')?->name ?? __('Plan')), 'href' => null],
            ]),
            'admin.payments.index' => $this->trail([$adminHub, ['label' => __('Invoice payments'), 'href' => route('admin.payments.index')]]),
            'admin.payment-gateways.edit', 'admin.payment-gateways.update' => $this->trail([
                $adminHub,
                ['label' => __('Payment gateways'), 'href' => route('admin.payment-gateways.edit')],
            ]),
            default => $this->trail([
                $adminHub,
                ['label' => Str::headline(Str::after($name, 'admin.')), 'href' => $adminHub['href']],
            ]),
        };
    }

    private function provider(string $name, Route $route): array
    {
        $hub = ['label' => __('Provider portal'), 'href' => route('provider.dashboard')];

        return match (true) {
            $name === 'provider.dashboard' => $this->trail([['label' => __('Provider portal'), 'href' => $hub['href']]]),
            $name === 'provider.partnership.show', $name === 'provider.partnership.sign' => $this->trail([
                $hub,
                ['label' => __('Provider partnership'), 'href' => route('provider.partnership.show')],
            ]),
            $name === 'provider.partnership.contract' => $this->trail([
                $hub,
                ['label' => __('Partnership contract'), 'href' => null],
            ]),
            $name === 'provider.projects.index' => $this->trail([$hub, ['label' => __('Projects'), 'href' => route('provider.projects.index')]]),
            $name === 'provider.projects.create' => $this->trail([
                $hub,
                ['label' => __('Projects'), 'href' => route('provider.projects.index')],
                ['label' => __('New project'), 'href' => route('provider.projects.create')],
            ]),
            $name === 'provider.projects.show' => $this->trail([
                $hub,
                ['label' => __('Projects'), 'href' => route('provider.projects.index')],
                ['label' => $this->projectLabel($route->parameter('project')), 'href' => null],
            ]),
            $name === 'provider.projects.edit' => $this->trail([
                $hub,
                ['label' => __('Projects'), 'href' => route('provider.projects.index')],
                ['label' => $this->projectLabel($route->parameter('project')), 'href' => route('provider.projects.show', $route->parameter('project'))],
                ['label' => __('Edit'), 'href' => null],
            ]),
            $name === 'provider.projects.proposals.create' => $this->trail([
                $hub,
                ['label' => __('Projects'), 'href' => route('provider.projects.index')],
                ['label' => $this->projectLabel($route->parameter('project')), 'href' => route('provider.projects.show', $route->parameter('project'))],
                ['label' => __('New proposal'), 'href' => null],
            ]),
            default => $this->trail([$hub, ['label' => Str::headline(str_replace('provider.', '', $name)), 'href' => $hub['href']]]),
        };
    }

    private function tenant(string $name, Route $route): array
    {
        $h = $this->homeCrumb();

        return match (true) {
            $name === 'dashboard' => $this->trail([['label' => $h['label'], 'href' => $h['href']]]),
            $name === 'portal.dashboard' => $this->trail([['label' => __('Portal'), 'href' => route('portal.dashboard')]]),
            $name === 'portal.client-account-requests.create', $name === 'portal.client-account-requests.store' => $this->trail([
                ['label' => __('Portal'), 'href' => route('portal.dashboard')],
                ['label' => __('Invite colleague'), 'href' => null],
            ]),
            str_starts_with($name, 'portal.') => $this->tenantPortal($name, $route, $h),
            $name === 'analytics.index' => $this->trail([$h, ['label' => __('Analytics'), 'href' => route('analytics.index')]]),
            $name === 'marketing.hub', $name === 'marketing.hub.update' => $this->trail([$h, ['label' => __('Marketing'), 'href' => route('marketing.hub')]]),
            str_starts_with($name, 'email-marketing.') => $this->tenantEmailMarketing($name, $route, $h),
            str_starts_with($name, 'hr.') => $this->tenantHr($name, $route, $h),
            $name === 'billing.index' => $this->trail([$h, ['label' => __('Billing'), 'href' => route('billing.index')]]),
            $name === 'notifications.index' => $this->trail([$h, ['label' => __('Activity'), 'href' => route('notifications.index')]]),
            $name === 'assistant.index', $name === 'assistant.chat', $name === 'assistant.suggest' => $this->trail([$h, ['label' => __('AI assistant'), 'href' => route('assistant.index')]]),
            $name === 'clients.index', $name === 'clients.store' => $this->trail([$h, ['label' => __('Clients'), 'href' => route('clients.index')]]),
            $name === 'clients.create' => $this->trail([
                $h,
                ['label' => __('Clients'), 'href' => route('clients.index')],
                ['label' => __('New client'), 'href' => route('clients.create')],
            ]),
            $name === 'clients.show' => $this->trail([
                $h,
                ['label' => __('Clients'), 'href' => route('clients.index')],
                ['label' => (string) ($route->parameter('client')?->name ?? __('Show')), 'href' => null],
            ]),
            $name === 'clients.edit', $name === 'clients.update' => $this->trail([
                $h,
                ['label' => __('Clients'), 'href' => route('clients.index')],
                [
                    'label' => (string) ($route->parameter('client')?->name ?? __('Client')),
                    'href' => ($c = $route->parameter('client')) ? route('clients.show', $c) : null,
                ],
                ['label' => __('Edit'), 'href' => null],
            ]),
            str_starts_with($name, 'clients.') => $this->trail([
                $h,
                ['label' => __('Clients'), 'href' => route('clients.index')],
                [
                    'label' => (string) ($route->parameter('client')?->name ?? __('Client')),
                    'href' => ($c = $route->parameter('client')) ? route('clients.show', $c) : null,
                ],
            ]),
            $name === 'clients.account-requests.index' => $this->trail([
                $h,
                ['label' => __('Clients'), 'href' => route('clients.index')],
                ['label' => __('Client signup requests'), 'href' => null],
            ]),
            str_starts_with($name, 'inquiries.') => $this->tenantInquiries($name, $route, $h),
            str_starts_with($name, 'projects.') => $this->tenantProjects($name, $route, $h),
            str_starts_with($name, 'invoices.') => $this->tenantInvoices($name, $route, $h),
            str_starts_with($name, 'proposals.') => $this->tenantProposals($name, $route, $h),
            str_starts_with($name, 'providers.') => $this->tenantProviders($name, $route, $h),
            str_starts_with($name, 'forms.') => $this->tenantForms($name, $route, $h),
            str_starts_with($name, 'settings.') => $this->tenantSettings($name, $route, $h),
            str_starts_with($name, 'modules.') => $this->tenantModules($name, $route, $h),
            str_starts_with($name, 'profile.') => $this->trail([$h, ['label' => __('Account'), 'href' => route('profile.edit')]]),
            str_starts_with($name, 'tickets.') => $this->tenantTickets($name, $route, $h),
            str_starts_with($name, 'chat.') => $this->tenantChat($name, $route, $h),
            $name === 'form-submissions.convert-project' => $this->trail([$h, ['label' => __('Forms'), 'href' => route('forms.index')]]),
            default => $this->tenantFallback($name, $route, $h),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantEmailMarketing(string $name, Route $route, array $h): array
    {
        $hub = ['label' => __('Email marketing'), 'href' => route('email-marketing.index')];
        $campaigns = ['label' => __('Campaigns'), 'href' => route('email-marketing.campaigns.index')];
        $audiences = ['label' => __('Audiences'), 'href' => route('email-marketing.audiences.index')];

        return match ($name) {
            'email-marketing.index' => $this->trail([$h, $hub]),
            'email-marketing.campaigns.index' => $this->trail([$h, $hub, $campaigns]),
            'email-marketing.campaigns.create', 'email-marketing.campaigns.store' => $this->trail([$h, $hub, $campaigns, ['label' => __('New'), 'href' => null]]),
            'email-marketing.campaigns.show' => $this->trail([
                $h, $hub, $campaigns,
                ['label' => Str::limit((string) ($route->parameter('campaign')?->name ?? ''), 48), 'href' => null],
            ]),
            'email-marketing.campaigns.edit', 'email-marketing.campaigns.update', 'email-marketing.campaigns.send' => $this->trail([
                $h, $hub, $campaigns,
                ['label' => Str::limit((string) ($route->parameter('campaign')?->name ?? ''), 48), 'href' => null],
            ]),
            'email-marketing.campaigns.destroy' => $this->trail([$h, $hub, $campaigns]),
            'email-marketing.campaigns.sample' => $this->trail([$h, $hub, $campaigns]),
            'email-marketing.templates.index' => $this->trail([$h, $hub, ['label' => __('Templates'), 'href' => route('email-marketing.templates.index')]]),
            'email-marketing.templates.create', 'email-marketing.templates.store' => $this->trail([$h, $hub, ['label' => __('Templates'), 'href' => route('email-marketing.templates.index')], ['label' => __('New'), 'href' => null]]),
            'email-marketing.templates.edit', 'email-marketing.templates.update' => $this->trail([
                $h, $hub, ['label' => __('Templates'), 'href' => route('email-marketing.templates.index')],
                ['label' => Str::limit((string) ($route->parameter('template')?->name ?? ''), 48), 'href' => null],
            ]),
            'email-marketing.templates.destroy' => $this->trail([$h, $hub, ['label' => __('Templates'), 'href' => route('email-marketing.templates.index')]]),
            'email-marketing.audiences.index' => $this->trail([$h, $hub, $audiences]),
            'email-marketing.audiences.create', 'email-marketing.audiences.store' => $this->trail([$h, $hub, $audiences, ['label' => __('New'), 'href' => null]]),
            'email-marketing.audiences.edit', 'email-marketing.audiences.update' => $this->trail([
                $h, $hub, $audiences,
                ['label' => Str::limit((string) ($route->parameter('audience')?->name ?? ''), 48), 'href' => null],
            ]),
            'email-marketing.audiences.destroy' => $this->trail([$h, $hub, $audiences]),
            'email-marketing.sequences.index' => $this->trail([$h, $hub, ['label' => __('Sequences'), 'href' => route('email-marketing.sequences.index')]]),
            default => $this->trail([$h, $hub]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantHr(string $name, Route $route, array $h): array
    {
        $hub = ['label' => __('HR & Payroll'), 'href' => route('hr.index')];
        $employees = ['label' => __('Employees'), 'href' => route('hr.employees.index')];
        $payroll = ['label' => __('hr_payroll'), 'href' => route('hr.payroll.index')];

        return match ($name) {
            'hr.index' => $this->trail([$h, $hub]),
            'hr.employees.index', 'hr.employees.store' => $this->trail([$h, $hub, $employees]),
            'hr.employees.create' => $this->trail([$h, $hub, $employees, ['label' => __('New'), 'href' => null]]),
            'hr.employees.show' => $this->trail([
                $h, $hub, $employees,
                ['label' => (string) ($route->parameter('employee')?->full_name ?? __('Show')), 'href' => null],
            ]),
            'hr.employees.edit', 'hr.employees.update' => $this->trail([
                $h, $hub, $employees,
                [
                    'label' => (string) ($route->parameter('employee')?->full_name ?? __('Employee')),
                    'href' => ($e = $route->parameter('employee')) ? route('hr.employees.show', $e) : null,
                ],
                ['label' => __('Edit'), 'href' => null],
            ]),
            'hr.departments.index', 'hr.departments.store', 'hr.departments.update', 'hr.departments.destroy' => $this->trail([
                $h, $hub, ['label' => __('hr_departments'), 'href' => route('hr.departments.index')],
            ]),
            'hr.leave.index', 'hr.leave.store', 'hr.leave.approve', 'hr.leave.reject' => $this->trail([
                $h, $hub, ['label' => __('hr_leave'), 'href' => route('hr.leave.index')],
            ]),
            'hr.payroll.index', 'hr.payroll.store' => $this->trail([$h, $hub, $payroll]),
            'hr.payroll.show', 'hr.payroll.generate', 'hr.payroll.finalize', 'hr.payroll.mark-paid' => $this->trail([
                $h, $hub, $payroll,
                ['label' => (string) ($route->parameter('payrollRun')?->title ?? __('Show')), 'href' => null],
            ]),
            default => $this->trail([$h, $hub]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantInquiries(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Inquiries'), 'href' => route('inquiries.index')];

        return match ($name) {
            'inquiries.index', 'inquiries.store' => $this->trail([$h, $idx]),
            'inquiries.create' => $this->trail([$h, $idx, ['label' => __('New inquiry'), 'href' => route('inquiries.create')]]),
            'inquiries.show' => $this->trail([
                $h, $idx,
                ['label' => Str::limit((string) ($route->parameter('inquiry')?->subject ?? $route->parameter('inquiry')?->id ?? ''), 48), 'href' => null],
            ]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantProjects(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Projects'), 'href' => route('projects.index')];
        $project = $route->parameter('project');

        if (str_contains($name, 'tasks.kanban')) {
            return $this->trail([
                $h, $idx,
                ['label' => $this->projectLabel($project), 'href' => $project ? route('projects.show', $project) : null],
                ['label' => __('Kanban'), 'href' => null],
            ]);
        }

        if (str_contains($name, 'tasks.gantt')) {
            return $this->trail([
                $h, $idx,
                ['label' => $this->projectLabel($project), 'href' => $project ? route('projects.show', $project) : null],
                ['label' => __('Gantt'), 'href' => null],
            ]);
        }

        if (str_contains($name, 'projects.tasks.') && $name !== 'projects.tasks.kanban' && $name !== 'projects.tasks.gantt') {
            return $this->trail([
                $h, $idx,
                ['label' => $this->projectLabel($project), 'href' => $project ? route('projects.show', $project) : null],
                ['label' => __('Tasks'), 'href' => null],
            ]);
        }

        return match ($name) {
            'projects.index', 'projects.store' => $this->trail([$h, $idx]),
            'projects.create' => $this->trail([$h, $idx, ['label' => __('New project'), 'href' => route('projects.create')]]),
            'projects.show' => $this->trail([
                $h, $idx,
                ['label' => $this->projectLabel($project), 'href' => null],
            ]),
            'projects.edit', 'projects.update', 'projects.team' => $this->trail([
                $h, $idx,
                ['label' => $this->projectLabel($project), 'href' => $project ? route('projects.show', $project) : null],
                ['label' => __('Edit'), 'href' => null],
            ]),
            'projects.files.store', 'projects.files.destroy' => $this->trail([
                $h, $idx,
                ['label' => $this->projectLabel($project), 'href' => $project ? route('projects.show', $project) : null],
                ['label' => __('Files'), 'href' => null],
            ]),
            'projects.destroy' => $this->trail([$h, $idx]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantInvoices(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Invoices'), 'href' => route('invoices.index')];
        $invoice = $route->parameter('invoice');

        return match (true) {
            in_array($name, ['invoices.index', 'invoices.store'], true) => $this->trail([$h, $idx]),
            $name === 'invoices.create' => $this->trail([$h, $idx, ['label' => __('New invoice'), 'href' => route('invoices.create')]]),
            $name === 'invoices.show' => $this->trail([
                $h, $idx,
                ['label' => $this->invoiceLabel($invoice), 'href' => null],
            ]),
            'invoices.edit', 'invoices.update', 'invoices.destroy' => $this->trail([
                $h, $idx,
                ['label' => $this->invoiceLabel($invoice), 'href' => $invoice ? route('invoices.show', $invoice) : null],
                ['label' => __('Edit invoice'), 'href' => null],
            ]),
            default => $this->trail([
                $h, $idx,
                ['label' => $this->invoiceLabel($invoice), 'href' => $invoice ? route('invoices.show', $invoice) : null],
            ]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantProposals(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Proposals'), 'href' => route('proposals.index')];
        $proposal = $route->parameter('proposal');

        return match (true) {
            in_array($name, ['proposals.index', 'proposals.store'], true) => $this->trail([$h, $idx]),
            $name === 'proposals.create' => $this->trail([$h, $idx, ['label' => __('New proposal'), 'href' => route('proposals.create')]]),
            $name === 'proposals.show' => $this->trail([
                $h, $idx,
                ['label' => $this->proposalLabel($proposal), 'href' => null],
            ]),
            'proposals.edit', 'proposals.update', 'proposals.destroy' => $this->trail([
                $h, $idx,
                ['label' => $this->proposalLabel($proposal), 'href' => $proposal ? route('proposals.show', $proposal) : null],
                ['label' => __('Edit proposal'), 'href' => null],
            ]),
            'proposals.invoice' => $this->trail([
                $h, $idx,
                ['label' => $this->proposalLabel($proposal), 'href' => $proposal ? route('proposals.show', $proposal) : null],
                ['label' => __('Invoice'), 'href' => null],
            ]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantProviders(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Providers'), 'href' => route('providers.index')];

        return match (true) {
            in_array($name, ['providers.index', 'providers.store'], true) => $this->trail([$h, $idx]),
            $name === 'providers.remittance-requests.index' => $this->trail([
                $h, $idx,
                ['label' => __('provider_remittance_inbox_title'), 'href' => null],
            ]),
            $name === 'providers.create' => $this->trail([$h, $idx, ['label' => __('New provider'), 'href' => route('providers.create')]]),
            'providers.edit', 'providers.update', 'providers.destroy' => $this->trail([
                $h, $idx,
                ['label' => (string) ($route->parameter('provider')?->name ?? __('Provider')), 'href' => null],
            ]),
            $name === 'providers.partnership.contract' => $this->trail([
                $h,
                $idx,
                [
                    'label' => (string) ($route->parameter('provider')?->name ?? __('Provider')),
                    'href' => ($p = $route->parameter('provider')) ? route('providers.edit', $p) : null,
                ],
                ['label' => __('Signed contract'), 'href' => null],
            ]),
            Str::startsWith($name, 'providers.partnership') => $this->trail([
                $h,
                $idx,
                [
                    'label' => (string) ($route->parameter('provider')?->name ?? __('Provider')),
                    'href' => ($p = $route->parameter('provider')) ? route('providers.edit', $p) : null,
                ],
                ['label' => __('Partnership signing'), 'href' => null],
            ]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantForms(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Forms'), 'href' => route('forms.index')];
        $form = $route->parameter('form');

        if ($name === 'forms.submissions.index') {
            return $this->trail([
                $h, $idx,
                ['label' => $this->formLabel($form), 'href' => $form ? route('forms.edit', $form) : null],
                ['label' => __('Submissions'), 'href' => null],
            ]);
        }

        return match (true) {
            in_array($name, ['forms.index', 'forms.store'], true) => $this->trail([$h, $idx]),
            $name === 'forms.create' => $this->trail([$h, $idx, ['label' => __('Create form'), 'href' => route('forms.create')]]),
            $name === 'forms.edit', $name === 'forms.update', $name === 'forms.destroy' => $this->trail([
                $h, $idx,
                ['label' => $this->formLabel($form), 'href' => null],
            ]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantSettings(string $name, Route $route, array $h): array
    {
        $settingsHub = ['label' => __('Company settings'), 'href' => route('settings.workspace')];

        if ($name === 'settings.workspace') {
            return $this->trail([$h, ['label' => __('Company settings'), 'href' => route('settings.workspace')]]);
        }

        $page = match (true) {
            Str::startsWith($name, 'settings.appearance') => __('Appearance & theme'),
            Str::startsWith($name, 'settings.dashboard') || Str::startsWith($name, 'settings.ui-presets') => __('Dashboard & widgets'),
            Str::startsWith($name, 'settings.branding') => __('Branding'),
            Str::startsWith($name, 'settings.workspace-currency') => __('Default currency'),
            Str::startsWith($name, 'settings.workspace-locale') => __('settings_workspace_locale_title'),
            Str::startsWith($name, 'settings.workspace-contact') => __('Workspace contact'),
            Str::startsWith($name, 'settings.provider-commissions') => __('Provider commission tiers'),
            Str::startsWith($name, 'settings.provider-recruitment') => __('Provider recruitment'),
            Str::startsWith($name, 'settings.widget-embed') => __('Widget embed'),
            Str::startsWith($name, 'settings.marketing-integrations') => __('Marketing integrations'),
            Str::startsWith($name, 'settings.modules') => __('settings_modules_title'),
            Str::startsWith($name, 'settings.navigation') => __('settings_navigation_title'),
            Str::startsWith($name, 'settings.smtp') => __('SMTP'),
            Str::startsWith($name, 'settings.google-calendar') => __('settings_connectivity_title'),
            Str::startsWith($name, 'settings.invoice-documents') => __('Invoice documents'),
            Str::startsWith($name, 'settings.billing-tax') => __('VAT & stamp'),
            Str::startsWith($name, 'settings.security') => __('Security'),
            Str::startsWith($name, 'settings.two-factor') => __('Two-factor authentication'),
            Str::startsWith($name, 'settings.team') => __('Team & roles'),
            default => Str::headline(str_replace('.', ' ', Str::after($name, 'settings.'))),
        };

        return $this->trail([$h, $settingsHub, ['label' => $page, 'href' => null]]);
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantModules(string $name, Route $route, array $h): array
    {
        $slug = (string) $route->parameter('slug');
        $module = InstalledModule::query()->where('slug', $slug)->first();
        $label = $module?->name ?? Str::headline($slug);

        return $this->trail([$h, ['label' => $label, 'href' => null]]);
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantTickets(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Tickets'), 'href' => route('tickets.index')];
        $ticket = $route->parameter('ticket');

        return match (true) {
            $name === 'tickets.index' => $this->trail([$h, $idx]),
            $name === 'tickets.create' => $this->trail([$h, $idx, ['label' => __('New ticket'), 'href' => route('tickets.create')]]),
            $name === 'tickets.show' => $this->trail([
                $h,
                $idx,
                ['label' => Str::limit((string) ($ticket instanceof SupportTicket ? $ticket->title : __('Ticket')), 48), 'href' => null],
            ]),
            'tickets.store', 'tickets.status' => $this->trail([$h, $idx]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantChat(string $name, Route $route, array $h): array
    {
        $idx = ['label' => __('Messages'), 'href' => route('chat.index')];

        return match (true) {
            $name === 'chat.index' => $this->trail([$h, $idx]),
            $name === 'chat.show' => $this->trail([
                $h, $idx,
                ['label' => $this->threadLabel($route->parameter('thread')), 'href' => null],
            ]),
            default => $this->trail([$h, $idx]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantPortal(string $name, Route $route, array $h): array
    {
        $portal = ['label' => __('Portal'), 'href' => route('portal.dashboard')];
        $project = $route->parameter('project');

        return match (true) {
            $name === 'portal.projects.index' => $this->trail([$portal, ['label' => __('My projects'), 'href' => route('portal.projects.index')]]),
            $name === 'portal.projects.show', $name === 'portal.projects.confirm-price' => $this->trail([
                $portal,
                ['label' => __('My projects'), 'href' => route('portal.projects.index')],
                ['label' => $this->projectLabel($project), 'href' => null],
            ]),
            str_contains($name, 'portal.projects.kanban') => $this->trail([
                $portal,
                ['label' => __('My projects'), 'href' => route('portal.projects.index')],
                ['label' => $this->projectLabel($project), 'href' => $project ? route('portal.projects.show', $project) : null],
                ['label' => __('Kanban'), 'href' => null],
            ]),
            str_contains($name, 'portal.projects.gantt') => $this->trail([
                $portal,
                ['label' => __('My projects'), 'href' => route('portal.projects.index')],
                ['label' => $this->projectLabel($project), 'href' => $project ? route('portal.projects.show', $project) : null],
                ['label' => __('Gantt'), 'href' => null],
            ]),
            $name === 'portal.proposals.index' => $this->trail([$portal, ['label' => __('Quotes'), 'href' => route('portal.proposals.index')]]),
            str_starts_with($name, 'portal.proposals.') => $this->trail([
                $portal,
                ['label' => __('Quotes'), 'href' => route('portal.proposals.index')],
                ['label' => $this->proposalLabel($route->parameter('proposal')), 'href' => null],
            ]),
            $name === 'portal.payments.index' => $this->trail([$portal, ['label' => __('Invoices'), 'href' => route('portal.payments.index')]]),
            $name === 'portal.quote-requests.index', $name === 'portal.quote-requests.store' => $this->trail([
                $portal,
                ['label' => __('portal_quote_requests'), 'href' => route('portal.quote-requests.index')],
            ]),
            $name === 'portal.quote-requests.create' => $this->trail([
                $portal,
                ['label' => __('portal_quote_requests'), 'href' => route('portal.quote-requests.index')],
                ['label' => __('portal_new_quote_request'), 'href' => null],
            ]),
            $name === 'portal.quote-requests.show' => $this->trail([
                $portal,
                ['label' => __('portal_quote_requests'), 'href' => route('portal.quote-requests.index')],
                ['label' => Str::limit((string) ($route->parameter('inquiry')?->subject ?? ''), 48), 'href' => null],
            ]),
            str_starts_with($name, 'portal.invoices.') => $this->trail([
                $portal,
                ['label' => __('Invoices'), 'href' => route('portal.payments.index')],
                ['label' => $this->invoiceLabel($route->parameter('invoice')), 'href' => null],
            ]),
            $name === 'portal.calendar', $name === 'portal.calendar.preview' => $this->trail([$portal, ['label' => __('Calendar'), 'href' => route('portal.calendar')]]),
            default => $this->trail([$portal]),
        };
    }

    /**
     * @param  array{label: string, href: string}  $h
     */
    private function tenantFallback(string $name, Route $route, array $h): array
    {
        $label = Str::headline(str_replace('.', ' · ', $name));

        return $this->trail([$h, ['label' => $label, 'href' => $h['href']]]);
    }

    private function projectLabel(mixed $project): string
    {
        return $project instanceof Project ? (string) ($project->name ?: $project->id) : __('Project');
    }

    private function invoiceLabel(mixed $invoice): string
    {
        if ($invoice instanceof Invoice) {
            return (string) ($invoice->number ?? $invoice->id);
        }

        return __('Invoice');
    }

    private function proposalLabel(mixed $proposal): string
    {
        return $proposal instanceof Proposal ? (string) ($proposal->name ?: $proposal->id) : __('Proposal');
    }

    private function formLabel(mixed $form): string
    {
        return $form instanceof Form ? (string) ($form->name ?: $form->id) : __('Form');
    }

    private function threadLabel(mixed $thread): string
    {
        return $thread instanceof ChatThread ? $thread->resolveSubjectName() : __('Conversation');
    }
}
