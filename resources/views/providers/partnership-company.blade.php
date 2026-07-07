<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Sign provider partnership') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="flow-panel p-6 shadow-sm">
                @include('partials.partnership-contract-display', [
                    'contractHeader' => $contractHeader,
                    'contractTerms' => $contractTerms,
                    'contractTermsIsHtml' => $contractTermsIsHtml ?? false,
                ])

                @if ($provider->partnership_provider_signed_at)
                    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('Provider signed at :datetime', ['datetime' => $provider->partnership_provider_signed_at->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                    </p>
                    <p class="mt-2">
                        <a
                            href="{{ route('providers.partnership.contract', $provider) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >{{ __('View signed contract (HTML)') }}</a>
                    </p>
                @endif

                @if ($provider->needsCompanyPartnershipSignature())
                    <form method="POST" action="{{ route('providers.partnership.sign', $provider) }}" class="mt-6 space-y-4">
                        @csrf
                        <label class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-300">
                            <input
                                type="checkbox"
                                name="accept"
                                value="1"
                                class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                                required
                            />
                            <span>{{ __('I confirm this partnership on behalf of :company.', ['company' => $provider->company->name]) }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('accept')" class="mt-2" />
                        <div class="flex flex-wrap gap-3">
                            <x-primary-button type="submit">{{ __('Sign as company') }}</x-primary-button>
                            <a href="{{ route('providers.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                {{ __('Back to providers') }}
                            </a>
                        </div>
                    </form>
                @else
                    <p class="mt-6 text-sm text-slate-600 dark:text-slate-400">{{ __('No signature is required at this stage.') }}</p>
                    <a href="{{ route('providers.index') }}" class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Back to providers') }}</a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
