@props(['metrics' => []])
@php
    $pc = $metrics['provider_commissions'] ?? null;
@endphp

@if (($flowdeskPlanGates['providers'] ?? true) && is_array($pc) && ($pc['provider_count'] ?? 0) > 0)
    @php $currency = $pc['currency'] ?? 'USD'; @endphp
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/[0.06]">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/80 bg-gradient-to-r from-violet-50/80 to-white px-6 py-4 dark:border-slate-700/80 dark:from-violet-950/30 dark:to-slate-900/40">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('dashboard_provider_commissions_title') }}</h3>
                <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">{{ __('dashboard_provider_commissions_lead') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a href="{{ route('providers.remittance-requests.index') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('provider_remittance_inbox_title') }} →</a>
                <a href="{{ route('providers.index') }}" class="font-medium text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">{{ __('view_providers') }} →</a>
            </div>
        </div>

        <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-flow.stat-card :label="__('provider_stat_commission_total')" variant="indigo">
                {{ flowdesk_format_minor((int) ($pc['commission_total_minor'] ?? 0), $currency) }} {{ $currency }}
            </x-flow.stat-card>
            <x-flow.stat-card :label="__('provider_stat_remitted')" variant="emerald">
                {{ flowdesk_format_minor((int) ($pc['remitted_minor'] ?? 0), $currency) }} {{ $currency }}
            </x-flow.stat-card>
            <x-flow.stat-card :label="__('provider_stat_pending_remittance')" variant="amber">
                {{ flowdesk_format_minor((int) ($pc['pending_remittance_minor'] ?? 0), $currency) }} {{ $currency }}
            </x-flow.stat-card>
            <x-flow.stat-card :label="__('provider_stat_balance_due')" variant="cyan">
                {{ flowdesk_format_minor((int) ($pc['balance_due_minor'] ?? 0), $currency) }} {{ $currency }}
            </x-flow.stat-card>
        </div>

        @php $pendingRequests = collect($pc['pending_requests'] ?? []); @endphp
        @if ($pendingRequests->isNotEmpty())
            <div class="border-t border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('provider_pending_payment_requests') }}</h4>
                    <a href="{{ route('providers.remittance-requests.index', ['status' => 'pending']) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('view_all') }} →</a>
                </div>
                <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($pendingRequests as $request)
                        <li class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">
                                    {{ $request->provider?->name ?? '—' }}
                                    <span class="ms-2 font-mono text-sm tabular-nums text-indigo-700 dark:text-indigo-300">{{ flowdesk_format_minor((int) $request->amount_minor, $currency) }} {{ $currency }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $request->created_at?->format('Y-m-d H:i') }} · {{ $request->payment_method?->label() }}</p>
                            </div>
                            @if ($request->provider)
                                <a href="{{ route('providers.edit', $request->provider) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    {{ __('Review') }}
                                    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
