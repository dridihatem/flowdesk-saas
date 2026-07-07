@props(['metrics' => []])
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <x-flow.card :title="__('dashboard_status_card_title')">
        <span class="text-lg font-semibold">{{ __('dashboard_status_logged_in') }}</span>
        <div class="mt-2">
            <x-flow.badge variant="success">{{ __('dashboard_status_session_active') }}</x-flow.badge>
        </div>
    </x-flow.card>

    @if(auth()->user()?->company)
        <x-flow.card :title="__('Company')">
            <p class="font-semibold">{{ auth()->user()->company->name }}</p>
            <p class="mt-1 text-sm text-flow-text-muted">{{ auth()->user()->company->subdomain }}</p>
        </x-flow.card>

        <x-flow.card :title="__('Default currency')">
            <p class="text-lg font-semibold">{{ auth()->user()->company->default_currency }}</p>
            @if(auth()->user()->company->country)
                <p class="mt-1 text-sm text-flow-text-muted">{{ auth()->user()->company->country }}</p>
            @endif
        </x-flow.card>
    @endif
</div>
