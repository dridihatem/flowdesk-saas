@props([
    'countries',
    'dialCodes',
    'phoneIsoId' => 'phone_country_iso',
    'nationalId' => 'phone_national_number',
    'nationalName' => 'phone_national_number',
])

@php
    $dialCodes = is_array($dialCodes) ? $dialCodes : [];
    $selectedIso = old('phone_country_iso', old('country'));
@endphp

<div class="grid gap-4 sm:grid-cols-5">
    <div class="sm:col-span-3">
        <x-input-label :for="$phoneIsoId" :value="__('Country code')" class="!text-slate-600 dark:!text-slate-400" />
        <select
            id="{{ $phoneIsoId }}"
            name="phone_country_iso"
            class="flow-input-select mt-2 block w-full"
        >
            <option value="">{{ __('— Select code —') }}</option>
            @foreach ($countries as $iso => $name)
                @php($dial = $dialCodes[$iso] ?? null)
                @if ($dial !== null && $dial !== '')
                    <option value="{{ $iso }}" @selected((string) $selectedIso === (string) $iso)>{{ $name }} (+{{ $dial }})</option>
                @endif
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('phone_country_iso')" class="mt-2" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label :for="$nationalId" :value="__('Phone number')" class="!text-slate-600 dark:!text-slate-400" />
        <input
            id="{{ $nationalId }}"
            name="{{ $nationalName }}"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="tel-national"
            data-flowdesk-digits-only
            value="{{ old('phone_national_number') }}"
            placeholder="{{ __('e.g. 12345678') }}"
            class="flow-input mt-2 block w-full tabular-nums flowdesk-ltr-num"
        />
        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Digits only, no spaces or symbols.') }}</p>
        <x-input-error :messages="$errors->get('phone_national_number')" class="mt-2" />
    </div>
</div>
