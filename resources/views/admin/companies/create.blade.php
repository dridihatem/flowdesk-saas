<x-admin-layout :title="__('Create company')">
    <div class="mb-6">
        <a
            href="{{ route('admin.companies.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Back to companies') }}</span>
        </a>
    </div>

    <x-flow.page-header
        :title="__('Create company')"
        :description="__('Create a new workspace and its first company admin user.')"
    />

    <form method="POST" action="{{ route('admin.companies.store') }}" class="mt-6 space-y-6">
        @csrf

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="flow-panel p-6">
                <h3 class="font-semibold text-slate-900">{{ __('Company') }}</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="company_name" :value="__('Company name')" />
                        <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" :value="old('company_name')" required />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="contact_email" :value="__('Company contact email (optional)')" />
                        <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full" :value="old('contact_email')" />
                        <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tax_id" :value="__('VAT / TVA number (optional)')" />
                        <x-text-input id="tax_id" name="tax_id" class="mt-1 block w-full" :value="old('tax_id')" placeholder="TVA / Matricule fiscal..." />
                        <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="default_locale" :value="__('Default language')" />
                            <select id="default_locale" name="default_locale" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" required>
                                @foreach (($locales ?? ['en']) as $loc)
                                    <option value="{{ $loc }}" @selected(old('default_locale', 'en') === $loc)>{{ flowdesk_locale_name($loc) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('default_locale')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="default_currency" :value="__('Default currency')" />
                            <select id="default_currency" name="default_currency" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" required>
                                @foreach (($currencies ?? ['USD']) as $code)
                                    @php($label = ($currencyLabels[$code] ?? $code))
                                    <option value="{{ $code }}" @selected(old('default_currency', 'USD') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="plan_id" :value="__('Initial plan (optional)')" />
                            <select id="plan_id" name="plan_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                                <option value="">{{ __('Use default') }}</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>
                                        {{ $plan->name }} ({{ $plan->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('plan_id')" class="mt-2" />
                        </div>

                        <label class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="hidden" name="plan_locked" value="0" />
                            <input type="checkbox" name="plan_locked" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500" @checked(old('plan_locked')) />
                            <span>{{ __('Lock plan') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flow-panel p-6">
                <h3 class="font-semibold text-slate-900">{{ __('First admin user') }}</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="admin_name" :value="__('Name')" />
                        <x-text-input id="admin_name" name="admin_name" class="mt-1 block w-full" :value="old('admin_name')" required />
                        <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="admin_email" :value="__('Email')" />
                        <x-text-input id="admin_email" name="admin_email" type="email" class="mt-1 block w-full" :value="old('admin_email')" required />
                        <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="admin_password" :value="__('Temporary password')" />
                        <x-text-input id="admin_password" name="admin_password" type="text" class="mt-1 block w-full" :value="old('admin_password')" required />
                        <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
                        <p class="mt-2 text-xs text-slate-500">{{ __('This password will be emailed (optional) and should be changed after first login.') }}</p>
                    </div>
                    <p class="text-xs text-slate-500">{{ __('A setup email will be sent to the admin email after creation (if mail is configured).') }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                <span>{{ __('Cancel') }}</span>
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                <span>{{ __('Create') }}</span>
            </button>
        </div>
    </form>
</x-admin-layout>

