{{-- Company fields after phone (shared by register wizard step 2 and company-profile-fields). --}}
<div class="mt-4">
    <x-input-label for="contact_email" :value="__('Company email (public)')" />
    <x-text-input id="contact_email" class="block mt-1 w-full" type="email" name="contact_email" :value="old('contact_email')" autocomplete="email" />
    <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="website" :value="__('Website (optional)')" />
    <x-text-input id="website" class="block mt-1 w-full" type="text" name="website" :value="old('website')" placeholder="https://…" />
    <x-input-error :messages="$errors->get('website')" class="mt-2" />
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="industry" :value="__('Industry (optional)')" />
        <x-text-input id="industry" class="block mt-1 w-full" type="text" name="industry" :value="old('industry')" />
        <x-input-error :messages="$errors->get('industry')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="tax_id" :value="__('Tax / VAT ID (optional)')" />
        <x-text-input id="tax_id" class="block mt-1 w-full" type="text" name="tax_id" :value="old('tax_id')" />
        <x-input-error :messages="$errors->get('tax_id')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="address_line1" :value="__('Address line (optional)')" />
    <x-text-input id="address_line1" class="block mt-1 w-full" type="text" name="address_line1" :value="old('address_line1')" />
    <x-input-error :messages="$errors->get('address_line1')" class="mt-2" />
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="city" :value="__('City')" />
        <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="old('city')" />
        <x-input-error :messages="$errors->get('city')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="postal_code" :value="__('Postal code')" />
        <x-text-input id="postal_code" class="block mt-1 w-full" type="text" name="postal_code" :value="old('postal_code')" />
        <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="default_currency" :value="__('Default currency (ISO-3)')" />
    <x-text-input id="default_currency" class="block mt-1 w-full uppercase" type="text" name="default_currency" maxlength="3" :value="old('default_currency')" placeholder="USD, EUR, TND…" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Used for proposals, quotes, and invoices. If empty, we infer from country when possible.') }}</p>
    <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
</div>
