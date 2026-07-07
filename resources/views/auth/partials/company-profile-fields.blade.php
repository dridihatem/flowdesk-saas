{{-- Shared between register (full) and OAuth company completion. --}}
<div class="mt-6 rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 text-sm text-indigo-950 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-100">
    <p class="font-medium">{{ __('Your company workspace') }}</p>
    <p class="mt-1 text-indigo-800/90 dark:text-indigo-200/90">{{ __('This organization is the account that owns clients, projects, pricing, quotes (proposals), and invoices. You can invite your team after signing up.') }}</p>
</div>

@php
    $countries = $countries ?? config('flowdesk_countries', []);
    $dialCodes = $dialCodes ?? config('flowdesk_country_dial_codes', []);
@endphp
@include('auth.partials.phone-fields', ['countries' => $countries, 'dialCodes' => $dialCodes])

@include('auth.partials.company-profile-info')
