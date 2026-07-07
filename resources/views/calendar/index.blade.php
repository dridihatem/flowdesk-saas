<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Calendar') }}</h2>
    </x-slot>

    <div class="flow-page-shell">
            <p class="mb-6 text-sm text-slate-600 dark:text-slate-400">
                {{ $isPortal ? __('calendar_portal_intro') : __('calendar_workspace_intro') }}
            </p>

            <x-calendar-app
                :events="$events"
                :month="$month"
                :calendly="$calendly"
                :is-portal="$isPortal"
                :google-calendar-connected="$googleCalendarConnected ?? false"
            />
    </div>
</x-app-layout>
