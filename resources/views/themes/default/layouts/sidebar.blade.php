<div class="min-h-screen flex flex-col lg:flex-row">
    <div class="lg:hidden">
        @include('layouts.navigation', ['flowdeskTheme' => $flowdeskTheme])
    </div>

    @include('layouts.navigation-sidebar', ['flowdeskTheme' => $flowdeskTheme])

    <div class="flow-workspace-main flex min-w-0 flex-1 flex-col">
        <div class="flow-chrome-topbar relative z-[40] flex items-center justify-end gap-2 overflow-visible border-b px-4 py-2 sm:px-6 lg:px-8">
            @auth
                <x-ai.nova-voice-nav />
            @endauth
            @if (! empty($flowdeskCalendarNav))
                <x-flow.calendar-nav-widget :preview="$flowdeskCalendarNav" />
            @endif
            <div class="hidden items-center gap-2 lg:flex">
            <a
                href="{{ route('notifications.index') }}"
                class="flow-notify-bell"
                title="{{ __('Activity') }}"
                aria-label="{{ __('Activity') }}"
            >
                <i class="fa-regular fa-bell text-lg" aria-hidden="true"></i>
            </a>
            <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-2">
                @csrf
                <label for="topbar_locale" class="sr-only">{{ __('Language') }}</label>
                <span class="hidden text-flow-text-muted xl:inline" aria-hidden="true">
                    <i class="fa-solid fa-language text-lg"></i>
                </span>
                <select
                    id="topbar_locale"
                    name="locale"
                    class="flow-chrome-select rounded-lg border py-1.5 ps-2 pe-8 text-sm shadow-sm focus:outline-none focus:ring-2"
                    onchange="this.form.submit()"
                >
                    @foreach (config('flowdesk.locales', ['en']) as $loc)
                        <option value="{{ $loc }}" @selected(app()->getLocale() === $loc)>{{ flowdesk_locale_name($loc) }}</option>
                    @endforeach
                </select>
            </form>
            <x-flow.topbar-profile-menu variant="topbar" />
            </div>
        </div>
        @isset($header)
            <header class="flow-chrome-header relative z-10 border-b backdrop-blur-md">
                <div class="max-w-12xl w-full py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        @if (! empty($flowdeskBreadcrumbs ?? []))
            <div class="flow-chrome-breadcrumb relative z-10 border-b backdrop-blur-sm">
                <div class="max-w-12xl w-full px-4 py-2.5 sm:px-6 lg:px-8">
                    <x-flow.breadcrumb-bar :items="$flowdeskBreadcrumbs" :back="$flowdeskBreadcrumbBack ?? null" />
                </div>
            </div>
        @endif

        <main class="flex-1 pb-16">
            @if (session('tenant_switch_notice'))
                <div class="max-w-12xl w-full px-4 pt-6 sm:px-6 lg:px-8">
                    <div class="rounded-xl border border-amber-200/90 bg-amber-50/95 px-4 py-3 text-sm text-amber-950 shadow-sm dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100">
                        {{ session('tenant_switch_notice') }}
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="max-w-12xl w-full px-4 pt-6 sm:px-6 lg:px-8">
                    <div class="rounded-xl border border-rose-200/90 bg-rose-50/95 px-4 py-3 text-sm text-rose-950 shadow-sm dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-100">
                        {{ session('error') }}
                    </div>
                </div>
            @endif
            <x-flow.trial-banner />
            {{ $slot }}
        </main>
        @auth
            <x-flow.chat-messenger-widget />
        @endauth
    </div>
</div>
