@php
    $groupsByKey = collect($profileGroups ?? [])->keyBy('key');
    $companyCards = $groupsByKey->get('company')['cards'] ?? [];
    $identityCards = $groupsByKey->get('identity')['cards'] ?? [];
    $marketingCards = $groupsByKey->get('marketing')['cards'] ?? [];
    $securityCards = $groupsByKey->get('security')['cards'] ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="flow-font-display text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="flow-page-shell">
        <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-base font-medium text-slate-800 dark:text-slate-200">{{ __('Profile page headline') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Profile page intro') }}</p>
            </div>

            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Profile') }}">
                @foreach ($profileNavGroups ?? [] as $nav)
                    @if ($nav['key'] === 'company' && empty($showWorkspaceProfile))
                        @continue
                    @endif
                    @if ($nav['key'] === 'identity' && empty($showWorkspaceProfile))
                        @continue
                    @endif
                    @if ($nav['key'] === 'marketing' && empty($showCompanyMarketingOnProfile))
                        @continue
                    @endif
                    <a
                        href="#{{ $nav['anchor'] }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-indigo-500/50 dark:hover:text-indigo-300"
                    >
                        {{ $nav['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-8">
            {{-- Compte personnel --}}
            <x-profile.section
                id="profile-group-account"
                :label="__('Profile group account')"
                :description="__('Profile group account description')"
            >
                <div class="max-w-xl space-y-8">
                    @include('profile.partials.update-profile-information-form')
                    @include('profile.partials.update-password-form')
                </div>
            </x-profile.section>

            @if (! empty($showWorkspaceProfile))
                {{-- Entreprise & contact --}}
                <x-profile.section
                    id="profile-group-company"
                    :label="__('Profile group company')"
                    :description="__('Profile group company description')"
                >
                    <div class="space-y-6">
                        @include('profile.partials.section-company-summary')
                        <x-profile.link-cards :cards="$companyCards" />
                    </div>
                </x-profile.section>

                {{-- Identité visuelle --}}
                <x-profile.section
                    id="profile-group-identity"
                    :label="__('Profile group identity')"
                    :description="__('Profile group identity description')"
                >
                    <div class="space-y-6">
                        @include('profile.partials.section-visual-identity')
                        <x-profile.link-cards :cards="$identityCards" />
                    </div>
                </x-profile.section>
            @endif

            @if (! empty($showCompanyMarketingOnProfile) && (($flowdeskPlanGates['marketing_hub'] ?? true) || ($flowdeskPlanGates['widgets'] ?? true)))
                {{-- Marketing & web --}}
                <x-profile.section
                    id="profile-group-marketing"
                    :label="__('Profile group marketing')"
                    :description="__('Profile group marketing description')"
                >
                    <div class="space-y-6">
                        @include('profile.partials.company-marketing-seo')
                        @if (count($marketingCards) > 0)
                            <div>
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Profile related settings') }}</h3>
                                <div class="mt-3 overflow-hidden rounded-xl ring-1 ring-slate-200/80 dark:ring-slate-700/80">
                                    <x-profile.link-cards :cards="$marketingCards" />
                                </div>
                            </div>
                        @endif
                    </div>
                </x-profile.section>
            @endif

            {{-- Sécurité & accès --}}
            <x-profile.section
                id="profile-group-security"
                :label="__('Profile group security')"
                :description="__('Profile group security description')"
            >
                <div class="space-y-6">
                    @if (count($securityCards) > 0)
                        <x-profile.link-cards :cards="$securityCards" />
                    @endif
                    <div class="max-w-xl border-t border-slate-200/80 pt-6 dark:border-slate-700/80">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </x-profile.section>
        </div>
    </div>
</x-app-layout>
