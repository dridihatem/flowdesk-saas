@php
    $currency = $summary['currency'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Provider portal') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Provider workspace') }}</h2>
            </div>
            @if ($provider->company)
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    <i class="fa-solid fa-building me-1 opacity-70" aria-hidden="true"></i>
                    {{ $provider->company->name }}
                </p>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            @if ($provider->needsProviderPartnershipSignature())
                <div class="overflow-hidden rounded-2xl border border-amber-200/90 bg-amber-50/90 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/30" role="status">
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500/20 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">
                                <i class="fa-solid fa-file-signature" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-amber-950 dark:text-amber-100">{{ __('Partnership contract required') }}</h3>
                                <p class="mt-1 text-sm text-amber-900/90 dark:text-amber-200/90">{{ __('You must sign the partnership contract before your workspace is fully active. Open the contract in a new tab, sign in the box, and send it.') }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('provider.partnership.contract') }}" target="_blank" rel="noopener noreferrer">
                                <x-primary-button type="button" class="!bg-amber-700 hover:!bg-amber-800 focus:!ring-amber-500">{{ __('Open contract to sign') }}</x-primary-button>
                            </a>
                            <a href="{{ route('provider.partnership.show') }}">
                                <x-secondary-button type="button">{{ __('Partnership details') }}</x-secondary-button>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-indigo-50/80 via-white to-white p-6 shadow-sm dark:border-slate-700/80 dark:from-indigo-950/30 dark:via-slate-900/50 dark:to-slate-900/40">
                <p class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('provider_welcome', ['name' => $provider->name]) }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('provider_dashboard_intro') }}</p>
            </div>

            @can('provider.view_commissions')
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-flow.stat-card :label="__('provider_stat_commission_total')" variant="indigo">
                        {{ flowdesk_format_minor((int) $summary['commission_total_minor'], $currency) }}
                        <span class="mt-1 block text-sm font-semibold text-indigo-800/90 dark:text-indigo-200/90">{{ $currency }}</span>
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('provider_stat_remitted')" variant="emerald">
                        {{ flowdesk_format_minor((int) $summary['remitted_minor'], $currency) }}
                        <span class="mt-1 block text-sm font-semibold text-emerald-800/90 dark:text-emerald-200/90">{{ $currency }}</span>
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('provider_stat_pending_remittance')" variant="amber">
                        {{ flowdesk_format_minor((int) $summary['pending_remittance_minor'], $currency) }}
                        <span class="mt-1 block text-sm font-semibold text-amber-800/90 dark:text-amber-200/90">{{ $currency }}</span>
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('provider_stat_balance_due')" variant="cyan">
                        {{ flowdesk_format_minor((int) $summary['balance_due_minor'], $currency) }}
                        <span class="mt-1 block text-sm font-semibold text-cyan-800/90 dark:text-cyan-200/90">{{ $currency }}</span>
                    </x-flow.stat-card>
                </div>
            @endcan

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2 space-y-6">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Recent projects') }}</h3>
                            @can('provider.manage_projects')
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('provider.projects.create') }}"><x-primary-button type="button" class="!text-xs !normal-case">{{ __('New project') }}</x-primary-button></a>
                                    <a href="{{ route('provider.projects.index') }}"><x-secondary-button type="button" class="!text-xs !normal-case">{{ __('My projects') }}</x-secondary-button></a>
                                </div>
                            @endcan
                        </div>
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($openProjects as $project)
                                <li class="flex items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                    <div>
                                        <a href="{{ route('provider.projects.show', $project) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $project->title }}</a>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $project->status->label() }}</p>
                                    </div>
                                    <div class="inline-flex items-center justify-end gap-1">
                                        @include('provider.partials.icon-action', [
                                            'href' => route('provider.projects.show', $project),
                                            'label' => __('View'),
                                            'icon' => 'fa-regular fa-eye',
                                        ])
                                    </div>
                                </li>
                            @empty
                                <li class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No projects yet.') }}</li>
                            @endforelse
                        </ul>
                    </div>

                    @can('provider.view_commissions')
                        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                            <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provider_recent_commissions') }}</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full table-fixed text-start text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200/80 bg-slate-50/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/40">
                                            <th class="px-5 py-3 text-start">{{ __('Project') }}</th>
                                            <th class="px-5 py-3 text-start">{{ __('Date') }}</th>
                                            <th class="px-5 py-3 text-end">{{ __('Commission') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @forelse ($recentCommissions as $negotiation)
                                            <tr>
                                                <td class="px-5 py-3 text-slate-900 dark:text-white text-start">{{ $negotiation->proposal?->project?->title ?? '—' }}</td>
                                                <td class="px-5 py-3 text-slate-600 dark:text-slate-400 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $negotiation->created_at->format('Y-m-d') }}</span></td>
                                                <td class="px-5 py-3 text-end font-medium"><span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $negotiation->commission_amount_minor, $currency) }} {{ $currency }}</span></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-5 py-10 text-center text-slate-500">{{ __('provider_no_commissions_yet') }}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endcan
                </div>

                <div class="space-y-6">
                    @can('provider.view_payments')
                        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                            <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('provider_payment_requests') }}</h3>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('provider_payment_requests_hint') }}</p>
                            </div>
                            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                                @forelse ($recentRemittanceRequests as $request)
                                    <li class="px-5 py-4">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $request->amount_minor, $currency) }} {{ $currency }}</p>
                                            <x-flow.badge :variant="$request->status->badgeVariant()">{{ $request->status->label() }}</x-flow.badge>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $request->created_at->format('Y-m-d') }} · {{ $request->payment_method?->label() ?? '—' }}</p>
                                    </li>
                                @empty
                                    <li class="px-5 py-8 text-center text-sm text-slate-500">{{ __('provider_no_payment_requests') }}</li>
                                @endforelse
                            </ul>
                            <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                                <a href="{{ route('provider.remittance-requests.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-800/50 dark:bg-indigo-950/40 dark:text-indigo-200">
                                    <i class="fa-solid fa-money-bill-transfer text-xs" aria-hidden="true"></i>
                                    {{ __('provider_submit_payment_request') }}
                                </a>
                            </div>
                        </div>
                    @endcan

                    <a href="{{ route('chat.index') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200/90 bg-white p-4 text-sm font-medium text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-700/80 dark:bg-slate-900/50 dark:text-slate-200">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400">
                            <i class="fa-solid fa-comments" aria-hidden="true"></i>
                        </span>
                        {{ __('Contact company') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
