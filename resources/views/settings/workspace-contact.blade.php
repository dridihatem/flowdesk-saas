<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Workspace contact') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Workspace contact intro') }}</p>

                <div class="mt-6 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm dark:border-slate-600/60 dark:bg-slate-800/40">
                    <span class="text-slate-500 dark:text-slate-400">{{ __('Company name') }}</span>
                    <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ $company->name }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Workspace contact name hint') }}</p>
                </div>

                <form method="POST" action="{{ route('settings.workspace-contact.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="contact_email" :value="__('Company email (public)')" />
                        <x-text-input
                            id="contact_email"
                            name="contact_email"
                            type="email"
                            class="mt-1 block w-full"
                            :value="old('contact_email', $company->contact_email)"
                            autocomplete="email"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('contact_email')" />
                    </div>

                    <div>
                        <x-input-label for="phone" :value="__('Company phone')" />
                        <x-text-input
                            id="phone"
                            name="phone"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('phone', $company->phone)"
                            autocomplete="tel"
                        />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Include country code if applicable.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                    </div>

                    <div>
                        <x-input-label for="website" :value="__('Website (optional)')" />
                        <x-text-input
                            id="website"
                            name="website"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('website', $company->website)"
                            placeholder="https://…"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('website')" />
                    </div>

                    <div>
                        <x-input-label for="country" :value="__('Country')" />
                        @include('auth.partials.country-select', [
                            'countries' => $countries,
                            'id' => 'country',
                            'name' => 'country',
                            'value' => old('country', $company->country),
                        ])
                        <x-input-error class="mt-2" :messages="$errors->get('country')" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="industry" :value="__('Industry (optional)')" />
                            <x-text-input id="industry" name="industry" type="text" class="mt-1 block w-full" :value="old('industry', $company->industry)" />
                            <x-input-error class="mt-2" :messages="$errors->get('industry')" />
                        </div>
                        <div>
                            <x-input-label for="tax_id" :value="__('Tax / VAT ID (optional)')" />
                            <x-text-input id="tax_id" name="tax_id" type="text" class="mt-1 block w-full" :value="old('tax_id', $company->tax_id)" />
                            <x-input-error class="mt-2" :messages="$errors->get('tax_id')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="address_line1" :value="__('Address line (optional)')" />
                        <x-text-input id="address_line1" name="address_line1" type="text" class="mt-1 block w-full" :value="old('address_line1', $company->address_line1)" />
                        <x-input-error class="mt-2" :messages="$errors->get('address_line1')" />
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="city" :value="__('City')" />
                            <x-text-input id="city" name="city" type="text" class="mt-1 block w-full" :value="old('city', $company->city)" />
                            <x-input-error class="mt-2" :messages="$errors->get('city')" />
                        </div>
                        <div>
                            <x-input-label for="postal_code" :value="__('Postal code')" />
                            <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full" :value="old('postal_code', $company->postal_code)" />
                            <x-input-error class="mt-2" :messages="$errors->get('postal_code')" />
                        </div>
                    </div>

                    <x-primary-button>{{ __('Save contact details') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
