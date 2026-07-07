@php
    $flowdeskTheme = $flowdeskTheme ?? [];
    $u = Auth::user();
    $isPureClient = $u && $u->hasRole('client') && ! $u->hasAnyRole(['company_admin', 'team_member', 'business_provider', 'platform_admin']);
    $isPureProvider = $u && $u->hasRole('business_provider') && ! $u->hasAnyRole(['company_admin', 'team_member', 'platform_admin']);
    $sidebarHome = $u?->hasRole('platform_admin')
        ? route('admin.dashboard')
        : ($isPureClient
            ? route('portal.dashboard')
            : ($isPureProvider ? route('provider.dashboard') : route('dashboard')));

    $navSectionActive = static fn (array $items): bool => collect($items)->contains(fn (array $item): bool => (bool) ($item['active'] ?? false));
@endphp
<aside
    class="flow-sidebar-aside hidden min-h-screen w-64 shrink-0 backdrop-blur-md lg:flex lg:flex-col"
>
    <div class="flow-sidebar-aside-header flex h-16 shrink-0 items-center gap-2 border-b px-2">
        <a
            href="{{ $sidebarHome }}"
            class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden rounded-lg px-2 py-1"
        >
            @if (! empty($flowdeskTheme['logo_url']))
                <img
                    src="{{ $flowdeskTheme['logo_url'] }}"
                    alt=""
                    class="h-8 max-w-[140px] w-auto object-contain"
                />
            @else
                <x-application-logo class="!gap-2 min-w-0" :tagline="false" :collapsible-wordmark="true" />
            @endif
        </a>
        @if ($u && $u->hasAnyRole(['company_admin', 'team_member']) && ! $isPureClient && ! $isPureProvider)
            <div class="ms-auto shrink-0">
                <x-flow.nav-ai-credits compact />
            </div>
        @endif
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-visible px-2 py-4">
        @if ($isPureClient)
            @php
                $portalNavItems = [
                    [
                        'url' => route('portal.dashboard'),
                        'label' => __('Overview'),
                        'icon' => 'dashboard',
                        'active' => request()->routeIs('portal.dashboard'),
                    ],
                ];
                if ($u->can('portal.view_projects')) {
                    $portalNavItems[] = [
                        'url' => route('portal.projects.index'),
                        'label' => __('My projects'),
                        'icon' => 'projects',
                        'active' => request()->routeIs('portal.projects.*'),
                    ];
                }
                if ($u->can('portal.view_proposals')) {
                    $portalNavItems[] = [
                        'url' => route('portal.proposals.index'),
                        'label' => __('Quotes'),
                        'icon' => 'fa-solid fa-file-contract',
                        'active' => request()->routeIs('portal.proposals.*'),
                    ];
                }
                if ($u->can('portal.request_quote')) {
                    $portalNavItems[] = [
                        'url' => route('portal.quote-requests.index'),
                        'label' => __('portal_quote_requests'),
                        'icon' => 'fa-solid fa-file-circle-plus',
                        'active' => request()->routeIs('portal.quote-requests.*'),
                    ];
                }
                if ($u->can('portal.view_invoices') || $u->can('portal.view_payments')) {
                    $portalNavItems[] = [
                        'url' => route('portal.payments.index'),
                        'label' => __('Invoices'),
                        'icon' => $u->can('portal.view_invoices') ? 'fa-solid fa-file-invoice' : 'billing',
                        'active' => request()->routeIs('portal.payments.*') || request()->routeIs('portal.invoices.*'),
                    ];
                }
                if ($flowdeskPlanGates['calendar'] ?? true) {
                    $portalNavItems[] = [
                        'url' => route('portal.calendar'),
                        'label' => __('Calendar'),
                        'icon' => 'calendar',
                        'active' => request()->routeIs('portal.calendar'),
                    ];
                }
                if ($u->can('portal.suggest_client_account')) {
                    $portalNavItems[] = [
                        'url' => route('portal.client-account-requests.create'),
                        'label' => __('Invite colleague'),
                        'icon' => 'fa-solid fa-user-plus',
                        'active' => request()->routeIs('portal.client-account-requests.*'),
                    ];
                }

                $clientSupportItems = [
                    [
                        'url' => route('chat.index'),
                        'label' => __('Messages'),
                        'icon' => 'messages',
                        'active' => request()->routeIs('chat.*'),
                    ],
                    [
                        'url' => route('tickets.index'),
                        'label' => __('Tickets'),
                        'icon' => 'tickets',
                        'active' => request()->routeIs('tickets.*'),
                    ],
                ];
            @endphp

            <x-flow.sidebar-nav-flyout
                :label="__('Portal')"
                icon="dashboard"
                :items="$portalNavItems"
                :active="$navSectionActive($portalNavItems)"
            />
            <x-flow.sidebar-nav-flyout
                :label="__('Nav section support')"
                icon="messages"
                :items="$clientSupportItems"
                :active="$navSectionActive($clientSupportItems)"
            />
            <x-flow.sidebar-nav-link
                :href="route('notifications.index')"
                :label="__('Activity')"
                icon="activity"
                :active="request()->routeIs('notifications.*')"
            />
            <x-flow.sidebar-nav-link
                :href="route('profile.edit')"
                :label="__('Account')"
                icon="profile"
                :active="request()->routeIs('profile.*')"
            />
        @elseif ($isPureProvider)
            @php
                $providerNavItems = [];
                if ($u->can('provider.view_dashboard')) {
                    $providerNavItems[] = [
                        'url' => route('provider.dashboard'),
                        'label' => __('Overview'),
                        'icon' => 'dashboard',
                        'active' => request()->routeIs('provider.dashboard'),
                    ];
                }
                if ($u->can('provider.manage_projects')) {
                    $providerNavItems[] = [
                        'url' => route('provider.projects.index'),
                        'label' => __('My projects'),
                        'icon' => 'projects',
                        'active' => request()->routeIs('provider.projects.*'),
                    ];
                }
                if ($u->can('provider.view_payments')) {
                    $providerNavItems[] = [
                        'url' => route('provider.remittance-requests.index'),
                        'label' => __('provider_payment_requests'),
                        'icon' => 'billing',
                        'active' => request()->routeIs('provider.remittance-requests.*'),
                    ];
                }

                $providerSupportItems = [
                    [
                        'url' => route('chat.index'),
                        'label' => __('Messages'),
                        'icon' => 'messages',
                        'active' => request()->routeIs('chat.*'),
                    ],
                    [
                        'url' => route('tickets.index'),
                        'label' => __('Tickets'),
                        'icon' => 'tickets',
                        'active' => request()->routeIs('tickets.*'),
                    ],
                ];
            @endphp

            <x-flow.sidebar-nav-flyout
                :label="__('Provider portal')"
                icon="provider"
                :items="$providerNavItems"
                :active="$navSectionActive($providerNavItems)"
            />
            <x-flow.sidebar-nav-flyout
                :label="__('Nav section support')"
                icon="messages"
                :items="$providerSupportItems"
                :active="$navSectionActive($providerSupportItems)"
            />
            <x-flow.sidebar-nav-link
                :href="route('notifications.index')"
                :label="__('Activity')"
                icon="activity"
                :active="request()->routeIs('notifications.*')"
            />
            <x-flow.sidebar-nav-link
                :href="route('profile.edit')"
                :label="__('Account')"
                icon="profile"
                :active="request()->routeIs('profile.*')"
            />
        @else
            @php
                $flowdeskNavSections = app(\App\Services\WorkspaceNavigationService::class)->sectionsFor($u, $flowdeskPlanGates ?? []);
            @endphp
            @foreach ($flowdeskNavSections as $navSection)
                @if ($navSection['flat'] ?? false)
                    @foreach ($navSection['items'] as $navItem)
                        <x-flow.sidebar-nav-link
                            :href="$navItem['url']"
                            :label="$navItem['label']"
                            :icon="$navItem['icon']"
                            :active="$navItem['active'] ?? false"
                        />
                    @endforeach
                @else
                    <x-flow.sidebar-nav-flyout
                        :label="$navSection['label']"
                        :icon="$navSection['icon'] ?? 'navigation'"
                        :items="$navSection['items']"
                        :active="$navSectionActive($navSection['items'])"
                    />
                @endif
            @endforeach

            @if (Auth::user()->hasAnyRole(['company_admin', 'team_member']))
                @if (($flowdeskPlanGates['modules'] ?? true) && ! empty($flowdeskInstalledModules))
                    @php
                        $moduleNavItems = collect($flowdeskInstalledModules)->map(fn (array $mod): array => [
                            'url' => $mod['route'],
                            'label' => $mod['name'],
                            'icon' => $mod['icon'],
                            'active' => request()->routeIs('modules.*') && request()->route('slug') === $mod['slug'],
                        ])->values()->all();
                    @endphp
                    <x-flow.sidebar-nav-flyout
                        :label="__('Nav section modules')"
                        icon="modules"
                        :items="$moduleNavItems"
                        :active="$navSectionActive($moduleNavItems)"
                    />
                @endif
                <x-flow.sidebar-nav-link
                    :href="route('settings.workspace')"
                    :label="__('Company settings')"
                    icon="settings"
                    :active="request()->routeIs('settings.*') || request()->routeIs('profile.*')"
                />
            @endif
        @endif
    </nav>
</aside>
