@php
    $currency = $summary['currency'] ?? 'USD';
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('provider_remittance_inbox_title') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            <x-flow.page-header :title="__('provider_remittance_inbox_title')">
                <x-slot name="actions">
                    <a href="{{ route('providers.index') }}">
                        <x-secondary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                            <i class="fa-solid fa-user-tie text-sm" aria-hidden="true"></i>
                            {{ __('Providers') }}
                        </x-secondary-button>
                    </a>
                </x-slot>
            </x-flow.page-header>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-flow.stat-card :label="__('provider_stat_commission_total')" variant="indigo">
                    {{ flowdesk_format_minor((int) ($summary['commission_total_minor'] ?? 0), $currency) }} {{ $currency }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('provider_stat_remitted')" variant="emerald">
                    {{ flowdesk_format_minor((int) ($summary['remitted_minor'] ?? 0), $currency) }} {{ $currency }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('provider_stat_pending_remittance')" variant="amber">
                    {{ flowdesk_format_minor((int) ($summary['pending_remittance_minor'] ?? 0), $currency) }} {{ $currency }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('provider_stat_balance_due')" variant="cyan">
                    {{ flowdesk_format_minor((int) ($summary['balance_due_minor'] ?? 0), $currency) }} {{ $currency }}
                </x-flow.stat-card>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('providers.remittance-requests.index') }}"
                   class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $status === '' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' }}">
                    {{ __('All') }}
                </a>
                <a href="{{ route('providers.remittance-requests.index', ['status' => 'pending']) }}"
                   class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium {{ $status === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' }}">
                    {{ __('Pending') }}
                    @if ($pendingCount > 0)
                        <span class="rounded-full bg-white/20 px-1.5 py-0.5 text-xs tabular-nums">{{ $pendingCount }}</span>
                    @endif
                </a>
                @foreach (['approved', 'rejected'] as $filterStatus)
                    <a href="{{ route('providers.remittance-requests.index', ['status' => $filterStatus]) }}"
                       class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium {{ $status === $filterStatus ? 'bg-slate-700 text-white dark:bg-slate-200 dark:text-slate-900' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700' }}">
                        {{ __('provider_remittance_status.'.$filterStatus) }}
                    </a>
                @endforeach
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Provider') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Method') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Reference') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($requests as $remittance)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-400 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $remittance->created_at->format('Y-m-d H:i') }}</span></td>
                                <td class="px-4 py-4 text-start">
                                    @if ($remittance->provider)
                                        <a href="{{ route('providers.edit', $remittance->provider) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $remittance->provider->name }}</a>
                                    @else
                                        —
                                    @endif
                                    @if ($remittance->notes)
                                        <p class="mt-1 max-w-xs truncate text-xs text-slate-500" title="{{ $remittance->notes }}">{{ $remittance->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-end font-semibold text-slate-900 dark:text-white"><span class="flowdesk-ltr-num tabular-nums font-semibold">{{ flowdesk_format_minor((int) $remittance->amount_minor, $currency) }} {{ $currency }}</span></td>
                                <td class="px-4 py-4 text-sm text-slate-700 dark:text-slate-300 text-start">{{ $remittance->payment_method?->label() ?? '—' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-400 text-start">{{ $remittance->reference ?? '—' }}</td>
                                <td class="px-4 py-4 text-start"><x-flow.badge :variant="$remittance->status->badgeVariant()">{{ $remittance->status->label() }}</x-flow.badge></td>
                                <td class="px-4 py-4 text-start">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($remittance->isPending() && $remittance->provider)
                                            @include('provider.partials.icon-action', [
                                                'formAction' => route('providers.remittance-requests.approve', [$remittance->provider, $remittance]),
                                                'label' => __('Approve'),
                                                'icon' => 'fa-solid fa-check',
                                                'variant' => 'success',
                                            ])
                                            @include('provider.partials.icon-action', [
                                                'formAction' => route('providers.remittance-requests.reject', [$remittance->provider, $remittance]),
                                                'label' => __('Reject'),
                                                'icon' => 'fa-solid fa-xmark',
                                                'variant' => 'danger',
                                            ])
                                        @endif
                                        @if ($remittance->provider)
                                            <a href="{{ route('providers.edit', $remittance->provider) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300" title="{{ __('View provider') }}">
                                                <span class="sr-only">{{ __('View provider') }}</span>
                                                <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">{{ __('provider_no_payment_requests') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
                @if ($requests->hasPages())
                    <div class="border-t border-slate-200/80 px-4 py-4 dark:border-slate-700/80">{{ $requests->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
