<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;

class WorkspaceNavigationService
{
    /**
     * Catalog of the toggleable staff sidebar items.
     * `visible` receives (User $user, array $gates) and applies the same
     * permission / plan-gate rules as the previous hardcoded sidebar.
     *
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        return [
            'dashboard' => [
                'section' => 'operations',
                'label' => fn () => __('Dashboard'),
                'icon' => 'dashboard',
                'route' => 'dashboard',
                'patterns' => ['dashboard'],
                'visible' => fn (User $u, array $g) => $u->can('workspace.view_dashboard'),
            ],
            'calendar' => [
                'section' => 'operations',
                'label' => fn () => __('Calendar'),
                'icon' => 'calendar',
                'route' => 'calendar.index',
                'patterns' => ['calendar.*'],
                'visible' => fn (User $u, array $g) => ($g['calendar'] ?? true) && $u->can('workspace.view_dashboard'),
            ],
            'clients' => [
                'section' => 'operations',
                'label' => fn () => __('Clients'),
                'icon' => 'clients',
                'route' => 'clients.index',
                'patterns' => ['clients.*'],
                'visible' => fn (User $u, array $g) => $u->can('workspace.manage_clients'),
            ],
            'projects' => [
                'section' => 'operations',
                'label' => fn () => __('Projects'),
                'icon' => 'projects',
                'route' => 'projects.index',
                'patterns' => ['projects.*'],
                'visible' => fn (User $u, array $g) => ($g['projects'] ?? true) && $u->can('workspace.manage_projects'),
            ],
            'inquiries' => [
                'section' => 'operations',
                'label' => fn () => __('Inquiries'),
                'icon' => 'inquiries',
                'route' => 'inquiries.index',
                'patterns' => ['inquiries.*'],
                'visible' => fn (User $u, array $g) => $u->can('workspace.manage_inquiries'),
            ],
            'proposals' => [
                'section' => 'sales_billing',
                'label' => fn () => __('Proposals'),
                'icon' => 'proposals',
                'route' => 'proposals.index',
                'patterns' => ['proposals.*'],
                'visible' => fn (User $u, array $g) => $u->can('workspace.manage_invoices'),
            ],
            'invoices' => [
                'section' => 'sales_billing',
                'label' => fn () => __('Invoices'),
                'icon' => 'invoices',
                'route' => 'invoices.index',
                'patterns' => ['invoices.*'],
                'visible' => fn (User $u, array $g) => $u->can('workspace.manage_invoices'),
            ],
            'billing' => [
                'section' => 'sales_billing',
                'label' => fn () => __('Plan subscription'),
                'icon' => 'billing',
                'route' => 'billing.index',
                'patterns' => ['billing.*'],
                'visible' => fn (User $u, array $g) => $u->can('workspace.manage_invoices') && $u->can('workspace.manage_subscription'),
            ],
            'analytics' => [
                'section' => 'insights',
                'label' => fn () => __('Analytics'),
                'icon' => 'analytics',
                'route' => 'analytics.index',
                'patterns' => ['analytics.*'],
                'visible' => fn (User $u, array $g) => ($g['analytics'] ?? true) && $u->can('workspace.view_analytics'),
            ],
            'reports' => [
                'section' => 'insights',
                'label' => fn () => __('Reports'),
                'icon' => 'fa-regular fa-file-lines',
                'route' => 'reports.index',
                'patterns' => ['reports.*'],
                'visible' => fn (User $u, array $g) => ($g['reports'] ?? true) && $u->can('workspace.view_analytics'),
            ],
            'marketing' => [
                'section' => 'marketing_growth',
                'label' => fn () => __('Marketing'),
                'icon' => 'marketing',
                'route' => 'marketing.hub',
                'patterns' => ['marketing.hub*'],
                'visible' => fn (User $u, array $g) => ($g['marketing_hub'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'forms' => [
                'section' => 'marketing_growth',
                'label' => fn () => __('Lead forms'),
                'icon' => 'forms',
                'route' => 'forms.index',
                'patterns' => ['forms.*'],
                'visible' => fn (User $u, array $g) => ($g['forms'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']) && $u->can('workspace.manage_projects'),
            ],
            'em_overview' => [
                'section' => 'email_marketing',
                'label' => fn () => __('Overview'),
                'icon' => 'email-marketing',
                'route' => 'email-marketing.index',
                'patterns' => ['email-marketing.index'],
                'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'em_campaigns' => [
                'section' => 'email_marketing',
                'label' => fn () => __('Campaigns'),
                'icon' => 'fa-solid fa-paper-plane',
                'route' => 'email-marketing.campaigns.index',
                'patterns' => ['email-marketing.campaigns.*'],
                'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'em_templates' => [
                'section' => 'email_marketing',
                'label' => fn () => __('Templates'),
                'icon' => 'fa-regular fa-file-lines',
                'route' => 'email-marketing.templates.index',
                'patterns' => ['email-marketing.templates.*'],
                'exclude_patterns' => ['email-marketing.templates.create'],
                'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'em_template_new' => [
                'section' => 'email_marketing',
                'label' => fn () => __('email_marketing_nav_new_template'),
                'icon' => 'fa-solid fa-plus',
                'route' => 'email-marketing.templates.create',
                'patterns' => ['email-marketing.templates.create'],
                'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'em_audiences' => [
                'section' => 'email_marketing',
                'label' => fn () => __('Audiences'),
                'icon' => 'fa-solid fa-users',
                'route' => 'email-marketing.audiences.index',
                'patterns' => ['email-marketing.audiences.*'],
                'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'em_sequences' => [
                'section' => 'email_marketing',
                'label' => fn () => __('Sequences'),
                'icon' => 'fa-solid fa-route',
                'route' => 'email-marketing.sequences.index',
                'patterns' => ['email-marketing.sequences.*'],
                'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'providers' => [
                'section' => 'partners',
                'label' => fn () => __('Providers'),
                'icon' => 'providers',
                'route' => 'providers.index',
                'patterns' => ['providers.*', 'providers.remittance-requests.*'],
                'visible' => fn (User $u, array $g) => ($g['providers'] ?? true) && $u->can('workspace.manage_providers'),
            ],
            'provider_portal' => [
                'section' => 'partners',
                'label' => fn () => __('Provider portal'),
                'hint' => fn () => __('provider_portal_nav_hint'),
                'icon' => 'provider',
                'route' => 'provider.dashboard',
                'patterns' => ['provider.*'],
                'visible' => fn (User $u, array $g) => $u->hasRole('business_provider'),
            ],
            'hr_overview' => [
                'section' => 'hr',
                'label' => fn () => __('Overview'),
                'icon' => 'fa-solid fa-gauge-high',
                'route' => 'hr.index',
                'patterns' => ['hr.index'],
                'visible' => fn (User $u, array $g) => ($g['hr'] ?? true) && $u->can('workspace.manage_hr'),
            ],
            'hr_employees' => [
                'section' => 'hr',
                'label' => fn () => __('Employees'),
                'icon' => 'fa-solid fa-id-badge',
                'route' => 'hr.employees.index',
                'patterns' => ['hr.employees.*'],
                'visible' => fn (User $u, array $g) => ($g['hr'] ?? true) && $u->can('workspace.manage_hr'),
            ],
            'hr_departments' => [
                'section' => 'hr',
                'label' => fn () => __('hr_departments'),
                'icon' => 'fa-solid fa-sitemap',
                'route' => 'hr.departments.index',
                'patterns' => ['hr.departments.*'],
                'visible' => fn (User $u, array $g) => ($g['hr'] ?? true) && $u->can('workspace.manage_hr'),
            ],
            'hr_leave' => [
                'section' => 'hr',
                'label' => fn () => __('hr_leave'),
                'icon' => 'fa-solid fa-plane-departure',
                'route' => 'hr.leave.index',
                'patterns' => ['hr.leave.*'],
                'visible' => fn (User $u, array $g) => ($g['hr'] ?? true) && $u->can('workspace.manage_hr'),
            ],
            'hr_payroll' => [
                'section' => 'hr',
                'label' => fn () => __('hr_payroll'),
                'icon' => 'fa-solid fa-money-check-dollar',
                'route' => 'hr.payroll.index',
                'patterns' => ['hr.payroll.*'],
                'visible' => fn (User $u, array $g) => ($g['hr'] ?? true) && $u->can('workspace.manage_hr'),
            ],
            'activity' => [
                'section' => 'productivity',
                'label' => fn () => __('Activity'),
                'icon' => 'activity',
                'route' => 'notifications.index',
                'patterns' => ['notifications.*'],
                'visible' => fn (User $u, array $g) => true,
            ],
            'assistant' => [
                'section' => 'productivity',
                'label' => fn () => __('AI assistant'),
                'icon' => 'ai',
                'route' => 'assistant.index',
                'patterns' => ['assistant.*'],
                'visible' => fn (User $u, array $g) => ($g['ai_credits'] ?? true) && $u->can('workspace.view_dashboard') && $u->hasAnyRole(['company_admin', 'team_member']),
            ],
            'messages' => [
                'section' => 'support',
                'label' => fn () => __('Messages'),
                'icon' => 'messages',
                'route' => 'chat.index',
                'patterns' => ['chat.*'],
                'visible' => fn (User $u, array $g) => true,
            ],
            'tickets' => [
                'section' => 'support',
                'label' => fn () => __('Tickets'),
                'icon' => 'tickets',
                'route' => 'tickets.index',
                'patterns' => ['tickets.*'],
                'visible' => fn (User $u, array $g) => true,
            ],
        ];
    }

    /**
     * Sidebar sections rendered as direct links (no flyout group).
     *
     * @return list<string>
     */
    public function flatSidebarSections(): array
    {
        return ['operations'];
    }

    /**
     * @return array<string, string> section key => label
     */
    public function sectionLabels(): array
    {
        return [
            'operations' => __('Nav section operations'),
            'sales_billing' => __('Nav section sales_billing'),
            'insights' => __('Nav section insights'),
            'marketing_growth' => __('Nav section marketing_growth'),
            'email_marketing' => __('Nav section email_marketing'),
            'partners' => __('Nav section partners'),
            'productivity' => __('Nav section productivity'),
            'support' => __('Nav section support'),
            'hr' => __('Nav section hr'),
        ];
    }

    /**
     * @return array<string, string> section key => nav-icon name
     */
    public function sectionIcons(): array
    {
        return [
            'operations' => 'dashboard',
            'sales_billing' => 'invoices',
            'insights' => 'analytics',
            'marketing_growth' => 'marketing',
            'email_marketing' => 'email-marketing',
            'partners' => 'providers',
            'productivity' => 'activity',
            'support' => 'messages',
            'hr' => 'fa-solid fa-people-group',
        ];
    }

    /**
     * @return list<string>
     */
    public function defaultSectionOrder(): array
    {
        $order = [];
        foreach ($this->catalog() as $item) {
            $section = $item['section'];
            if (! in_array($section, $order, true)) {
                $order[] = $section;
            }
        }

        return $order;
    }

    /**
     * @return array{hidden: list<string>, order: list<string>, section_order: list<string>}
     */
    public function preferencesFor(Company $company): array
    {
        $settings = CompanySetting::query()->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        $nav = is_array($settings?->navigation) ? $settings->navigation : [];

        $hidden = array_values(array_filter(
            (array) ($nav['hidden'] ?? []),
            fn ($v) => is_string($v),
        ));
        $order = array_values(array_filter(
            (array) ($nav['order'] ?? []),
            fn ($v) => is_string($v),
        ));
        $sectionOrder = array_values(array_filter(
            (array) ($nav['section_order'] ?? []),
            fn ($v) => is_string($v),
        ));

        return ['hidden' => $hidden, 'order' => $order, 'section_order' => $sectionOrder];
    }

    /**
     * @param  array{hidden?: list<string>, order?: list<string>, section_order?: list<string>}  $preferences
     */
    public function savePreferences(Company $company, array $preferences): void
    {
        $keys = array_keys($this->catalog());
        $hidden = array_values(array_intersect($preferences['hidden'] ?? [], $keys));
        $order = array_values(array_intersect($preferences['order'] ?? [], $keys));
        $validSections = $this->defaultSectionOrder();
        $sectionOrder = array_values(array_intersect($preferences['section_order'] ?? [], $validSections));

        $settings = CompanySetting::query()->withoutGlobalScopes()
            ->firstOrCreate(['company_id' => $company->id]);
        $settings->update([
            'navigation' => [
                'hidden' => $hidden,
                'order' => $order,
                'section_order' => $sectionOrder,
            ],
        ]);
    }

    /**
     * Sections + items ready for the sidebar: filtered by permissions/gates,
     * minus the hidden items, ordered by the workspace preferences.
     *
     * @param  array<string, bool>  $gates
     * @return list<array{key: string, label: string, items: list<array<string, mixed>>}>
     */
    public function sectionsFor(User $user, array $gates): array
    {
        $company = $user->company;
        $prefs = $company ? $this->preferencesFor($company) : ['hidden' => [], 'order' => [], 'section_order' => []];

        $items = $this->orderedCatalog($prefs['order']);
        $labels = $this->sectionLabels();
        $icons = $this->sectionIcons();
        $sections = [];

        foreach ($items as $key => $item) {
            if (in_array($key, $prefs['hidden'], true)) {
                continue;
            }
            if (! ($item['visible'])($user, $gates)) {
                continue;
            }

            $section = $item['section'];
            $sections[$section] ??= [
                'key' => $section,
                'label' => $labels[$section] ?? $section,
                'icon' => $icons[$section] ?? 'navigation',
                'flat' => in_array($section, $this->flatSidebarSections(), true),
                'items' => [],
            ];
            $sections[$section]['items'][] = [
                'key' => $key,
                'label' => ($item['label'])(),
                'icon' => $item['icon'],
                'url' => route($item['route']),
                'active' => $this->isActive($item),
            ];
        }

        return $this->orderedSections($sections, $prefs['section_order']);
    }

    /**
     * Catalog grouped by section for the settings page (permissions ignored —
     * the admin manages the full list), with hidden state and saved order.
     *
     * @return list<array{key: string, label: string, items: list<array<string, mixed>>}>
     */
    public function manageableSectionsFor(Company $company): array
    {
        $prefs = $this->preferencesFor($company);
        $items = $this->orderedCatalog($prefs['order']);
        $labels = $this->sectionLabels();
        $sections = [];

        foreach ($items as $key => $item) {
            $section = $item['section'];
            $sections[$section] ??= [
                'key' => $section,
                'label' => $labels[$section] ?? $section,
                'items' => [],
            ];
            $entry = [
                'key' => $key,
                'label' => ($item['label'])(),
                'icon' => $item['icon'],
                'enabled' => ! in_array($key, $prefs['hidden'], true),
            ];
            if (isset($item['hint'])) {
                $entry['hint'] = ($item['hint'])();
            }
            $sections[$section]['items'][] = $entry;
        }

        return $this->orderedSections($sections, $prefs['section_order']);
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     * @param  list<string>  $sectionOrder
     * @return list<array<string, mixed>>
     */
    private function orderedSections(array $sections, array $sectionOrder): array
    {
        if ($sectionOrder === []) {
            return array_values($sections);
        }

        $sorted = [];
        foreach ($sectionOrder as $key) {
            if (isset($sections[$key])) {
                $sorted[] = $sections[$key];
                unset($sections[$key]);
            }
        }

        foreach ($sections as $section) {
            $sorted[] = $section;
        }

        return $sorted;
    }

    /**
     * @param  list<string>  $order
     * @return array<string, array<string, mixed>>
     */
    private function orderedCatalog(array $order): array
    {
        $catalog = $this->catalog();
        if ($order === []) {
            return $catalog;
        }

        $sorted = [];
        foreach ($order as $key) {
            if (isset($catalog[$key])) {
                $sorted[$key] = $catalog[$key];
                unset($catalog[$key]);
            }
        }

        // Items unknown to the saved order (e.g. added later) keep catalog order.
        return $sorted + $catalog;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isActive(array $item): bool
    {
        $active = request()->routeIs(...$item['patterns']);
        if ($active && ! empty($item['exclude_patterns'])) {
            $active = ! request()->routeIs(...$item['exclude_patterns']);
        }

        return $active;
    }
}
