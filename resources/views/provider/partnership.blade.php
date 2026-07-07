<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Provider partnership') }}</h2>
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

                @if ($provider->needsProviderPartnershipSignature())
                    <div class="mt-6 space-y-4">
                        <p class="text-sm text-slate-700 dark:text-slate-300">
                            {{ __('Open the contract in a new tab to read the full agreement, sign in the box, and send it to register your signature.') }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('provider.partnership.contract') }}" target="_blank" rel="noopener noreferrer">
                                <x-primary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                                    <i class="fa-solid fa-file-signature text-sm" aria-hidden="true"></i>
                                    {{ __('Open contract to sign') }}
                                </x-primary-button>
                            </a>
                        </div>
                    </div>
                @elseif ($provider->needsCompanyPartnershipSignature())
                    <p class="mt-6 text-sm font-medium text-amber-800 dark:text-amber-200">
                        {{ __('You have signed. Waiting for a company administrator to sign on behalf of :company.', ['company' => $provider->company->name]) }}
                    </p>
                    <p class="mt-3">
                        <a
                            href="{{ route('provider.partnership.contract') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                            {{ __('View your signed contract') }}
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
