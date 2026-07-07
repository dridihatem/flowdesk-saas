<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Complete your workspace for') }} <strong>{{ $pending['email'] }}</strong>
    </div>

    <form method="POST" action="{{ route('oauth.company.store') }}">
        @csrf

        <div>
            <x-input-label for="company_name" :value="__('Company name')" />
            <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autofocus autocomplete="organization" />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="country" :value="__('Country')" />
            @include('auth.partials.country-select', ['countries' => $countries, 'id' => 'country', 'name' => 'country', 'value' => old('country')])
            <x-input-error :messages="$errors->get('country')" class="mt-2" />
        </div>

        <div class="mt-4">
            @include('auth.partials.vat-percent-field')
        </div>

        @include('auth.partials.company-profile-fields', ['countries' => $countries, 'dialCodes' => $dialCodes])

        <div class="flex items-center justify-end mt-6">
            <x-primary-button>
                {{ __('Create workspace') }}
            </x-primary-button>
        </div>
    </form>

    @include('auth.partials.country-registration-data')
</x-guest-layout>
