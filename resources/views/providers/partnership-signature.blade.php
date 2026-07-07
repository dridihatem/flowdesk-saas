<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Provider signature on file') }}</h2>
            <a href="{{ route('providers.index') }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Back to providers') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8">
            <div class="flow-panel space-y-4 p-6 shadow-sm">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('provider_contract_summary_line', ['company' => $provider->company->name, 'provider' => $provider->name]) }}
                </p>
                @if ($provider->partnership_provider_signed_at)
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Signed electronically on :datetime', ['datetime' => $provider->partnership_provider_signed_at->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                    </p>
                @endif
                <div class="rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-600 dark:bg-slate-900/60">
                    <img
                        src="{{ $provider->partnership_provider_signature_data }}"
                        alt="{{ __('Provider signature') }}"
                        class="mx-auto max-h-48 w-auto max-w-full rounded-lg border border-slate-200 dark:border-slate-600"
                    />
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('This image is what the provider drew when signing the partnership contract.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
