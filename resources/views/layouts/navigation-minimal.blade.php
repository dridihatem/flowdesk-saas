@php($flowdeskTheme = $flowdeskTheme ?? [])
<header class="flow-glass-nav sticky top-0 z-40">
    <div class="max-w-12xl w-full flex min-h-14 flex-wrap items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2">
            @if (! empty($flowdeskTheme['logo_url']))
                <img src="{{ $flowdeskTheme['logo_url'] }}" alt="" class="h-7 w-auto max-w-[120px] object-contain" />
            @else
                <x-application-logo class="!gap-2" :tagline="false" />
            @endif
        </a>
        <div class="flex flex-1 flex-wrap items-center justify-end gap-x-1 gap-y-2 text-sm">
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
            @if (Auth::user()->hasAnyRole(['company_admin', 'team_member']))
                <x-nav-link :href="route('inquiries.index')" :active="request()->routeIs('inquiries.*')">
                    {{ __('Inquiries') }}
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
                <x-nav-link :href="route('email-marketing.index')" :active="request()->routeIs('email-marketing.*')">
                    {{ __('Email marketing') }}
                </x-nav-link>
            @endif
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
            <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                {{ __('Activity') }}
            </x-nav-link>
            @if ($flowdeskPlanGates['ai_credits'] ?? true)
                <x-nav-link :href="route('assistant.index')" :active="request()->routeIs('assistant.*')">
                    {{ __('AI assistant') }}
                </x-nav-link>
            @endif
            @if (Auth::user()->hasAnyRole(['company_admin', 'team_member']))
                <x-nav-link :href="route('settings.workspace')" :active="request()->routeIs('settings.*')">
                    {{ __('Company settings') }}
                </x-nav-link>
            @endif
            <x-flow.nav-ai-credits compact />
            <form method="POST" action="{{ route('locale.update') }}" class="hidden sm:block">
                @csrf
                <select name="locale" class="rounded-lg border-slate-200 bg-white/90 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" onchange="this.form.submit()">
                    @foreach (config('flowdesk.locales', ['en']) as $loc)
                        <option value="{{ $loc }}" @selected(app()->getLocale() === $loc)>{{ flowdesk_locale_name($loc) }}</option>
                    @endforeach
                </select>
            </form>
            <x-dropdown align="right" width="56">
                <x-slot name="trigger">
                    <button type="button" class="inline-flex max-w-[10rem] items-center rounded-lg border border-slate-200/80 bg-white/80 px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <span class="truncate">{{ Auth::user()->name }}</span>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                    @if (Auth::user()->hasAnyRole(['company_admin', 'team_member']))
                        @if ($flowdeskPlanGates['marketing_hub'] ?? true)
                            <x-dropdown-link :href="route('marketing.hub')">{{ __('Marketing') }}</x-dropdown-link>
                        @endif
                        @if ($flowdeskPlanGates['email_marketing'] ?? true)
                            <x-dropdown-link :href="route('email-marketing.index')">{{ __('Email marketing') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('email-marketing.campaigns.index')">{{ __('Campaigns') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('email-marketing.templates.index')">{{ __('Templates') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('email-marketing.templates.create')">{{ __('email_marketing_nav_new_template') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('email-marketing.audiences.index')">{{ __('Audiences') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('email-marketing.sequences.index')">{{ __('Sequences') }}</x-dropdown-link>
                        @endif
                        <x-dropdown-link :href="route('settings.workspace')">{{ __('Company settings') }}</x-dropdown-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
