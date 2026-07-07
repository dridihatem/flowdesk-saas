<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Billing, VAT & fiscal stamp') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel p-8">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('These defaults apply to new and updated invoices: subtotal from line items, then VAT and optional fiscal stamp (fixed amount in your workspace default currency :cur), then total.', ['cur' => $company->default_currency ?? 'USD']) }}</p>

                <form method="POST" action="{{ route('settings.billing-tax.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="fiscal_stamp_enabled" value="0" />

                    <div>
                        <x-input-label for="vat_percent" :value="__('Default VAT % (TVA)')" />
                        <x-text-input id="vat_percent" name="vat_percent" type="text" inputmode="decimal" class="mt-1 block w-full" placeholder="19" :value="$vat_percent" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave empty for no VAT.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('vat_percent')" />
                    </div>

                    <div class="flex items-center gap-3">
                        <input id="fiscal_stamp_enabled" type="checkbox" name="fiscal_stamp_enabled" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600" @checked(filter_var($fiscal_stamp_enabled, FILTER_VALIDATE_BOOL) || $fiscal_stamp_enabled === '1') />
                        <x-input-label for="fiscal_stamp_enabled" :value="__('Apply fiscal stamp (timbre fiscal)')" class="!mb-0" />
                    </div>

                    <div>
                        <x-input-label for="fiscal_stamp_amount" :value="__('Fiscal stamp amount (:cur)', ['cur' => $company->default_currency ?? 'USD'])" />
                        <x-text-input id="fiscal_stamp_amount" name="fiscal_stamp_amount" type="text" inputmode="decimal" class="mt-1 block w-full" placeholder="0" :value="$fiscal_stamp_amount" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('Fixed amount added once per invoice when enabled, in normal decimals for that currency (e.g. 1 for one dinar).') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('fiscal_stamp_amount')" />
                    </div>

                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
