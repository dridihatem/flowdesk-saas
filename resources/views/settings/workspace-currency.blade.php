<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Workspace default currency') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('This currency is pre-selected when you create invoices and proposals. You can still pick another currency on each document.') }}</p>

                <form method="POST" action="{{ route('settings.workspace-currency.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="default_currency" :value="__('Default currency')" />
                        <x-currency-select
                            id="default_currency"
                            name="default_currency"
                            :options="$currencyOptions"
                            :value="old('default_currency', $company->default_currency)"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('default_currency')" />
                    </div>

                    <x-primary-button>{{ __('Save default currency') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
