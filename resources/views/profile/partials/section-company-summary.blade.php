@php
    $company = $profileCompany ?? null;
    $branding = is_array($profileBranding ?? null) ? $profileBranding : [];
    $countries = config('flowdesk_countries', []);
    $countryCode = $company?->country;
    $countryLabel = is_string($countryCode) && $countryCode !== '' ? ($countries[$countryCode] ?? $countryCode) : null;
@endphp

@if ($company)
    <dl class="grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Company name') }}</dt>
            <dd class="mt-0.5 font-semibold text-slate-900 dark:text-slate-100">{{ $company->name }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Default currency') }}</dt>
            <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ strtoupper((string) ($company->default_currency ?? '—')) }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Company email (public)') }}</dt>
            <dd class="mt-0.5 break-all text-slate-800 dark:text-slate-200">{{ $company->contact_email ?: '—' }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Company phone') }}</dt>
            <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $company->phone ?: '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Address') }}</dt>
            <dd class="mt-0.5 text-slate-800 dark:text-slate-200">
                @php
                    $addressParts = array_filter([
                        $company->address_line1,
                        trim(implode(' ', array_filter([$company->postal_code, $company->city]))),
                        $countryLabel,
                    ]);
                @endphp
                {{ $addressParts !== [] ? implode(', ', $addressParts) : '—' }}
            </dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Industry') }}</dt>
            <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $company->industry ?: '—' }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Tax ID') }}</dt>
            <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ $company->tax_id ?: '—' }}</dd>
        </div>
        @if (trim((string) ($branding['tagline'] ?? '')) !== '')
            <div class="sm:col-span-2">
                <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Tagline') }}</dt>
                <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ $branding['tagline'] }}</dd>
            </div>
        @endif
        @if (trim((string) ($branding['support_email'] ?? '')) !== '')
            <div>
                <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Support email') }}</dt>
                <dd class="mt-0.5 break-all text-slate-800 dark:text-slate-200">{{ $branding['support_email'] }}</dd>
            </div>
        @endif
        @if (trim((string) ($branding['website_url'] ?? '')) !== '')
            <div>
                <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Website URL') }}</dt>
                <dd class="mt-0.5 break-all text-slate-800 dark:text-slate-200">{{ $branding['website_url'] }}</dd>
            </div>
        @endif
    </dl>
@endif
