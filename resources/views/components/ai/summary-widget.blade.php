@props([
    'summary' => [],
    'compact' => false,
])

@php
    $growth = $summary['growth_percent'] ?? null;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/80 bg-white/90 p-5 shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/60 dark:ring-white/10']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('nova_summary_title') }}</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $summary['assistant_name'] ?? config('app.name').' '.config('flowdesk.ai_assistant_brand_name', 'Nova') }}</p>
        </div>
        @isset($summary['assistant_url'])
            <a href="{{ $summary['assistant_url'] }}" class="text-xs font-semibold text-sky-600 hover:text-sky-500 dark:text-sky-400">{{ __('nova_open_full') }}</a>
        @endisset
    </div>

    <div @class(['mt-4 grid gap-3', $compact ? 'grid-cols-2 sm:grid-cols-4' : 'grid-cols-2 lg:grid-cols-4'])>
        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('Clients') }}</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($summary['clients_count'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('nova_active_projects') }}</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($summary['active_projects'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('nova_monthly_revenue') }}</p>
            <p class="mt-1 text-lg font-bold tabular-nums text-slate-900 dark:text-white">{{ $summary['monthly_revenue_formatted'] ?? '—' }}</p>
        </div>
        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('nova_unpaid_invoices') }}</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($summary['unpaid_invoices'] ?? 0) }}</p>
        </div>
    </div>

    @if ($growth !== null)
        <p class="mt-3 text-xs text-slate-600 dark:text-slate-400">
            {{ __('nova_growth_label') }}:
            <span @class([
                'font-semibold tabular-nums',
                'text-emerald-600 dark:text-emerald-400' => $growth >= 0,
                'text-rose-600 dark:text-rose-400' => $growth < 0,
            ])>{{ $growth >= 0 ? '+' : '' }}{{ $growth }}%</span>
        </p>
    @endif

    @if (! empty($summary['recommendations']))
        <div class="mt-4 border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('nova_recommendations') }}</p>
            <ul class="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                @foreach ($summary['recommendations'] as $tip)
                    <li class="flex gap-2">
                        <span class="text-sky-500">•</span>
                        <span>{{ $tip }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
