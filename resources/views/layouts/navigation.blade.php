@php
    $flowdeskTheme = $flowdeskTheme ?? [];
    $flowdeskUser = Auth::user();
    $isPureClient = $flowdeskUser && $flowdeskUser->hasRole('client') && ! $flowdeskUser->hasAnyRole(['company_admin', 'team_member', 'business_provider', 'platform_admin']);
    $isPureProvider = $flowdeskUser && $flowdeskUser->hasRole('business_provider') && ! $flowdeskUser->hasAnyRole(['company_admin', 'team_member', 'platform_admin']);
    $flowdeskHome = $flowdeskUser?->hasRole('platform_admin')
        ? route('admin.dashboard')
        : ($isPureClient
            ? route('portal.dashboard')
            : ($isPureProvider
                ? route('provider.dashboard')
                : route('dashboard')));
@endphp
<nav x-data="{ open: false }" class="flow-glass-nav sticky top-0 z-40">
    <div class="max-w-12xl w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <div class="shrink-0 flex items-center">
                    <a href="{{ $flowdeskHome }}" class="flex items-center gap-2 rounded-lg ring-indigo-500/20 focus:outline-none focus:ring-2">
                        @if (! empty($flowdeskTheme['logo_url']))
                            <img src="{{ $flowdeskTheme['logo_url'] }}" alt="" class="block h-9 w-auto max-w-[140px] object-contain" />
                        @else
                            <x-application-logo class="!gap-2" :tagline="false" />
                        @endif
                    </a>
                </div>

                <div class="hidden min-w-0 flex-1 items-center gap-2 lg:flex">
                    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-1 gap-y-1 xl:gap-x-2">
                        @if ($isPureClient)
                            <x-nav-link :href="route('portal.dashboard')" :active="request()->routeIs('portal.dashboard')">
                                {{ __('Overview') }}
                            </x-nav-link>
                            @can('portal.view_projects')
                                <x-nav-link :href="route('portal.projects.index')" :active="request()->routeIs('portal.projects.*')">
                                    {{ __('My projects') }}
                                </x-nav-link>
                            @endcan
                            @can('portal.view_proposals')
                                <x-nav-link :href="route('portal.proposals.index')" :active="request()->routeIs('portal.proposals.*')">
                                    {{ __('Quotes') }}
                                </x-nav-link>
                            @endcan
                            @can('portal.request_quote')
                                <x-nav-link :href="route('portal.quote-requests.index')" :active="request()->routeIs('portal.quote-requests.*')">
                                    {{ __('portal_quote_requests') }}
                                </x-nav-link>
                            @endcan
                            @can('portal.view_invoices')
                                <x-nav-link :href="route('portal.payments.index')" :active="request()->routeIs('portal.payments.*') || request()->routeIs('portal.invoices.*')">
                                    {{ __('Invoices') }}
                                </x-nav-link>
                            @elsecan('portal.view_payments')
                                <x-nav-link :href="route('portal.payments.index')" :active="request()->routeIs('portal.payments.*') || request()->routeIs('portal.invoices.*')">
                                    {{ __('Invoices') }}
                                </x-nav-link>
                            @endcan
                            @can('portal.suggest_client_account')
                                <x-nav-link :href="route('portal.client-account-requests.create')" :active="request()->routeIs('portal.client-account-requests.*')">
                                    {{ __('Invite colleague') }}
                                </x-nav-link>
                            @endcan
                        @elseif ($isPureProvider)
                            @can('provider.view_dashboard')
                                <x-nav-link :href="route('provider.dashboard')" :active="request()->routeIs('provider.dashboard')">
                                    {{ __('Overview') }}
                                </x-nav-link>
                            @endcan
                            @can('provider.manage_projects')
                                <x-nav-link :href="route('provider.projects.index')" :active="request()->routeIs('provider.projects.*')">
                                    {{ __('My projects') }}
                                </x-nav-link>
                            @endcan
                            @can('provider.view_payments')
                                <x-nav-link :href="route('provider.remittance-requests.index')" :active="request()->routeIs('provider.remittance-requests.*')">
                                    {{ __('provider_payment_requests') }}
                                </x-nav-link>
                            @endcan
                        @elseif ($flowdeskUser?->hasRole('platform_admin'))
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                                {{ __('Admin') }}
                            </x-nav-link>
                        @else
                            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                                {{ __('Dashboard') }}
                            </x-nav-link>
                            <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                                {{ __('Clients') }}
                            </x-nav-link>
                            @if ($flowdeskPlanGates['projects'] ?? true)
                                <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                                    {{ __('Projects') }}
                                </x-nav-link>
                            @endif
                            @if ($flowdeskUser?->hasAnyRole(['company_admin', 'team_member']))
                                <x-nav-link :href="route('inquiries.index')" :active="request()->routeIs('inquiries.*')">
                                    {{ __('Inquiries') }}
                                </x-nav-link>
                            @endif
                            <x-nav-link :href="route('proposals.index')" :active="request()->routeIs('proposals.*')">
                                {{ __('Quotes') }}
                            </x-nav-link>
                            <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                                {{ __('Invoices') }}
                            </x-nav-link>
                            @if ($flowdeskUser?->hasRole('company_admin'))
                                <x-nav-link :href="route('billing.index')" :active="request()->routeIs('billing.*')">
                                    {{ __('Plan subscription') }}
                                </x-nav-link>
                            @endif
                            @if ($flowdeskPlanGates['analytics'] ?? true)
                                <x-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')">
                                    {{ __('Analytics') }}
                                </x-nav-link>
                            @endif
                            @if ($flowdeskPlanGates['marketing_hub'] ?? true)
                                <x-nav-link :href="route('marketing.hub')" :active="request()->routeIs('marketing.hub*')">
                                    {{ __('Marketing') }}
                                </x-nav-link>
                            @endif
                            @if ($flowdeskPlanGates['email_marketing'] ?? true)
                                <x-dropdown align="left" width="56" wrapperClass="relative z-[120] inline-flex" floatingClass="z-[130]" contentClasses="py-1 overflow-hidden bg-white/95 backdrop-blur-md dark:bg-slate-800/95 dark:ring-1 dark:ring-slate-600/80">
                                    <x-slot name="trigger">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white @if(request()->routeIs('email-marketing.*')) bg-slate-100 text-slate-900 dark:bg-slate-800/80 dark:text-white @endif"
                                            aria-haspopup="true"
                                            aria-expanded="false"
                                        >
                                            {{ __('Email marketing') }}
                                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400" aria-hidden="true"></i>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <div class="border-b border-slate-100 px-3 py-2 dark:border-slate-700/80">
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Campaigns & lists') }}</p>
                                        </div>
                                        <x-dropdown-link :href="route('email-marketing.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                            {{ __('Overview') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('email-marketing.campaigns.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                            {{ __('Campaigns') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('email-marketing.templates.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                            {{ __('Templates') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('email-marketing.templates.create')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                            {{ __('email_marketing_nav_new_template') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('email-marketing.audiences.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                            {{ __('Audiences') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('email-marketing.sequences.index')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                            {{ __('Sequences') }}
                                        </x-dropdown-link>
                                    </x-slot>
                                </x-dropdown>
                            @endif
                            <x-dropdown align="left" width="56" wrapperClass="relative z-[120] inline-flex" floatingClass="z-[130]" contentClasses="py-1 overflow-hidden bg-white/95 backdrop-blur-md dark:bg-slate-800/95 dark:ring-1 dark:ring-slate-600/80">
                                <x-slot name="trigger">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition duration-150 ease-in-out hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/80 dark:hover:text-white"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        {{ __('Product website') }}
                                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400" aria-hidden="true"></i>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <div class="border-b border-slate-100 px-3 py-2 dark:border-slate-700/80">
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('SEO & public pages') }}</p>
                                    </div>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('Home') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/features')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('Features') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/pricing')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('Pricing') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/about')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('About us') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/contact')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('Contact') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/terms')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('Terms of service') }}
                                    </x-dropdown-link>
                                    <x-dropdown-link :href="flowdesk_public_site_url('/privacy')" class="dark:text-slate-200 dark:hover:bg-slate-700/80">
                                        {{ __('Privacy policy') }}
                                    </x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                            @if ($flowdeskPlanGates['reports'] ?? true)
                                <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                                    {{ __('Reports') }}
                                </x-nav-link>
                            @endif
                            @if ($flowdeskPlanGates['providers'] ?? true)
                                <x-nav-link :href="route('providers.index')" :active="request()->routeIs('providers.*')">
                                    {{ __('Providers') }}
                                </x-nav-link>
                            @endif
                            @if ($flowdeskPlanGates['forms'] ?? true)
                                <x-nav-link :href="route('forms.index')" :active="request()->routeIs('forms.*')">
                                    {{ __('Lead forms') }}
                                </x-nav-link>
                            @endif
                        @endif
                        <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                            {{ __('Activity') }}
                        </x-nav-link>
                        @if (! $flowdeskUser?->hasAnyRole(['client', 'business_provider', 'platform_admin']) && ($flowdeskPlanGates['ai_credits'] ?? true))
                            <x-nav-link :href="route('assistant.index')" :active="request()->routeIs('assistant.*')">
                                {{ __('AI assistant') }}
                            </x-nav-link>
                        @endif
                        <x-nav-link :href="route('chat.index')" :active="request()->routeIs('chat.*')">
                            {{ __('Messages') }}
                        </x-nav-link>
                        <x-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
                            {{ __('Tickets') }}
                        </x-nav-link>
                    </div>
                    @if ($flowdeskUser?->hasAnyRole(['company_admin', 'team_member']))
                        <div class="flex shrink-0 items-center border-s border-slate-200/80 ps-3 dark:border-slate-600/80">
                            <x-flow.nav-ai-credits />
                        </div>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-3 sm:ms-4">
                @if (! empty($flowdeskCalendarNav))
                    <x-flow.calendar-nav-widget :preview="$flowdeskCalendarNav" />
                @endif
                <a
                    href="{{ route('notifications.index') }}"
                    class="flow-notify-bell !h-9 !w-9"
                    title="{{ __('Activity') }}"
                    aria-label="{{ __('Activity') }}"
                >
                    <i class="fa-regular fa-bell text-base" aria-hidden="true"></i>
                </a>
                <x-flow.nav-ai-credits class="lg:hidden" />
                <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-2 text-sm">
                    @csrf
                    <label for="nav_locale" class="sr-only">{{ __('Language') }}</label>
                    <select
                        id="nav_locale"
                        name="locale"
                        class="rounded-lg border-slate-200 bg-white/90 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                        onchange="this.form.submit()"
                    >
                        @foreach (config('flowdesk.locales', ['en']) as $loc)
                            <option value="{{ $loc }}" @selected(app()->getLocale() === $loc)>{{ flowdesk_locale_name($loc) }}</option>
                        @endforeach
                    </select>
                </form>

                <x-flow.topbar-profile-menu variant="nav" />
            </div>

            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" type="button" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <span class="sr-only">{{ __('Open menu') }}</span>
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200/70 dark:border-slate-700/60 lg:hidden">
        <div class="space-y-1 px-4 pb-4 pt-2">
            @if ($isPureClient)
                <x-responsive-nav-link :href="route('portal.dashboard')" :active="request()->routeIs('portal.dashboard')">
                    {{ __('Overview') }}
                </x-responsive-nav-link>
                @can('portal.view_projects')
                    <x-responsive-nav-link :href="route('portal.projects.index')" :active="request()->routeIs('portal.projects.*')">
                        {{ __('My projects') }}
                    </x-responsive-nav-link>
                @endcan
                @can('portal.view_proposals')
                    <x-responsive-nav-link :href="route('portal.proposals.index')" :active="request()->routeIs('portal.proposals.*')">
                        {{ __('Quotes') }}
                    </x-responsive-nav-link>
                @endcan
                @can('portal.request_quote')
                    <x-responsive-nav-link :href="route('portal.quote-requests.index')" :active="request()->routeIs('portal.quote-requests.*')">
                        {{ __('portal_quote_requests') }}
                    </x-responsive-nav-link>
                @endcan
                @can('portal.view_invoices')
                    <x-responsive-nav-link :href="route('portal.payments.index')" :active="request()->routeIs('portal.payments.*') || request()->routeIs('portal.invoices.*')">
                        {{ __('Invoices') }}
                    </x-responsive-nav-link>
                @elsecan('portal.view_payments')
                    <x-responsive-nav-link :href="route('portal.payments.index')" :active="request()->routeIs('portal.payments.*') || request()->routeIs('portal.invoices.*')">
                        {{ __('Invoices') }}
                    </x-responsive-nav-link>
                @endcan
                @can('portal.suggest_client_account')
                    <x-responsive-nav-link :href="route('portal.client-account-requests.create')" :active="request()->routeIs('portal.client-account-requests.*')">
                        {{ __('Invite colleague') }}
                    </x-responsive-nav-link>
                @endcan
            @elseif ($isPureProvider)
                @can('provider.view_dashboard')
                    <x-responsive-nav-link :href="route('provider.dashboard')" :active="request()->routeIs('provider.dashboard')">
                        {{ __('Overview') }}
                    </x-responsive-nav-link>
                @endcan
                @can('provider.manage_projects')
                    <x-responsive-nav-link :href="route('provider.projects.index')" :active="request()->routeIs('provider.projects.*')">
                        {{ __('My projects') }}
                    </x-responsive-nav-link>
                @endcan
                @can('provider.view_payments')
                    <x-responsive-nav-link :href="route('provider.remittance-requests.index')" :active="request()->routeIs('provider.remittance-requests.*')">
                        {{ __('provider_payment_requests') }}
                    </x-responsive-nav-link>
                @endcan
            @elseif ($flowdeskUser?->hasRole('platform_admin'))
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                    {{ __('Clients') }}
                </x-responsive-nav-link>
                @if ($flowdeskPlanGates['projects'] ?? true)
                    <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                        {{ __('Projects') }}
                    </x-responsive-nav-link>
                @endif
                @if ($flowdeskUser?->hasAnyRole(['company_admin', 'team_member']))
                    <x-responsive-nav-link :href="route('inquiries.index')" :active="request()->routeIs('inquiries.*')">
                        {{ __('Inquiries') }}
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('proposals.index')" :active="request()->routeIs('proposals.*')">
                    {{ __('Quotes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                    {{ __('Invoices') }}
                </x-responsive-nav-link>
                @if ($flowdeskUser?->hasRole('company_admin'))
                    <x-responsive-nav-link :href="route('billing.index')" :active="request()->routeIs('billing.*')">
                        {{ __('Billing') }}
                    </x-responsive-nav-link>
                @endif
                @if ($flowdeskPlanGates['analytics'] ?? true)
                    <x-responsive-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')">
                        {{ __('Analytics') }}
                    </x-responsive-nav-link>
                @endif
                @if ($flowdeskPlanGates['marketing_hub'] ?? true)
                    <x-responsive-nav-link :href="route('marketing.hub')" :active="request()->routeIs('marketing.hub*')">
                        {{ __('Marketing') }}
                    </x-responsive-nav-link>
                @endif
                @if ($flowdeskPlanGates['email_marketing'] ?? true)
                    <p class="px-4 pt-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email marketing') }}</p>
                    <x-responsive-nav-link :href="route('email-marketing.index')" :active="request()->routeIs('email-marketing.index')">
                        {{ __('Overview') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('email-marketing.campaigns.index')" :active="request()->routeIs('email-marketing.campaigns.*')">
                        {{ __('Campaigns') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link
                        :href="route('email-marketing.templates.index')"
                        :active="request()->routeIs('email-marketing.templates.*') && ! request()->routeIs('email-marketing.templates.create')"
                    >
                        {{ __('Templates') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('email-marketing.templates.create')" :active="request()->routeIs('email-marketing.templates.create')">
                        {{ __('email_marketing_nav_new_template') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('email-marketing.audiences.index')" :active="request()->routeIs('email-marketing.audiences.*')">
                        {{ __('Audiences') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('email-marketing.sequences.index')" :active="request()->routeIs('email-marketing.sequences.*')">
                        {{ __('Sequences') }}
                    </x-responsive-nav-link>
                @endif
                <p class="px-4 pt-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Product website') }}</p>
                <x-responsive-nav-link :href="flowdesk_public_site_url('/features')">{{ __('Features') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="flowdesk_public_site_url('/pricing')">{{ __('Pricing') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="flowdesk_public_site_url('/about')">{{ __('About us') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="flowdesk_public_site_url('/contact')">{{ __('Contact') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="flowdesk_public_site_url('/terms')">{{ __('Terms of service') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="flowdesk_public_site_url('/privacy')">{{ __('Privacy policy') }}</x-responsive-nav-link>
                @if ($flowdeskPlanGates['reports'] ?? true)
                    <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                        {{ __('Reports') }}
                    </x-responsive-nav-link>
                @endif
                @if ($flowdeskPlanGates['providers'] ?? true)
                    <x-responsive-nav-link :href="route('providers.index')" :active="request()->routeIs('providers.*')">
                        {{ __('Providers') }}
                    </x-responsive-nav-link>
                @endif
                @if ($flowdeskPlanGates['forms'] ?? true)
                    <x-responsive-nav-link :href="route('forms.index')" :active="request()->routeIs('forms.*')">
                        {{ __('Lead forms') }}
                    </x-responsive-nav-link>
                @endif
            @endif
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                {{ __('Activity') }}
            </x-responsive-nav-link>
            @if (! $flowdeskUser?->hasAnyRole(['client', 'business_provider', 'platform_admin']) && ($flowdeskPlanGates['ai_credits'] ?? true))
                <x-responsive-nav-link :href="route('assistant.index')" :active="request()->routeIs('assistant.*')">
                    {{ __('AI assistant') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('chat.index')" :active="request()->routeIs('chat.*')">
                {{ __('Messages') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
                {{ __('Tickets') }}
            </x-responsive-nav-link>
            @if ($flowdeskUser?->hasAnyRole(['company_admin', 'team_member']))
                <x-responsive-nav-link :href="route('settings.workspace')" :active="request()->routeIs('settings.*') || request()->routeIs('profile.*')">
                    {{ __('Company settings') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-slate-200/70 px-4 py-4 dark:border-slate-700/60">
            <div class="font-medium text-slate-800 dark:text-slate-100">{{ Auth::user()->name }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">{{ Auth::user()->email }}</div>
            <div class="mt-3">
                <x-flow.nav-ai-credits compact />
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
