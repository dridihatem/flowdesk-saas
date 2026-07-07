<div>
    <x-input-label for="vat_percent" :value="__('Default VAT % (TVA)')" class="!text-slate-600 dark:!text-slate-400" />
    <x-text-input
        id="vat_percent"
        name="vat_percent"
        type="text"
        inputmode="decimal"
        class="mt-2 block w-full"
        :value="old('vat_percent')"
        placeholder="19"
        data-flowdesk-vat-field
    />
    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('VAT country hint') }}</p>
    <x-input-error :messages="$errors->get('vat_percent')" class="mt-2" />
</div>
