<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Send estimate') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            <div class="flow-panel p-8">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Project') }}: <strong>{{ $project->title }}</strong></p>
                <form method="POST" action="{{ route('provider.projects.proposals.store', $project) }}" class="mt-6 space-y-6">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Proposal title')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', __('Estimate')) }}" required />
                    </div>
                    <div>
                        <x-input-label for="amount" :value="__('Amount (minor units)')" />
                        <x-text-input id="amount" name="amount" type="number" class="mt-1 block w-full" value="{{ old('amount') }}" required min="0" />
                        <p class="mt-1 text-xs text-slate-500">{{ __('e.g. 500000 for 5,000.00 in two-decimal currency') }}</p>
                    </div>
                    <div>
                        <x-input-label for="currency" :value="__('Currency')" />
                        <x-currency-select
                            id="currency"
                            name="currency"
                            :options="$currencyOptions"
                            :value="old('currency', $project->company?->default_currency ?? auth()->user()->company?->default_currency ?? 'USD')"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('currency')" />
                    </div>
                    <div>
                        <x-input-label for="valid_until" :value="__('Valid until')" />
                        <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full" :value="old('valid_until')" />
                    </div>
                    <div>
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800">{{ old('notes') }}</textarea>
                    </div>
                    <div class="flex gap-3">
                        <x-primary-button type="submit">{{ __('Send') }}</x-primary-button>
                        <a href="{{ route('provider.projects.show', $project) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
