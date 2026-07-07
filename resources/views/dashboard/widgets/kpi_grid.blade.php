@props(['metrics' => [], 'nova' => null])
@php
    $byCurrency = $metrics['outstanding_by_currency'] ?? [];
    if ($byCurrency === [] && array_key_exists('outstanding_amount_minor', $metrics)) {
        $byCurrency = [($metrics['currency'] ?? 'USD') => (int) ($metrics['outstanding_amount_minor'] ?? 0)];
    }
    $hasCalendar = ! empty($flowdeskCalendarNav);
    $showTopRow = $nova || $hasCalendar;
@endphp

@if ($showTopRow)
    <div @class(['grid gap-4', ($nova && $hasCalendar) ? 'lg:grid-cols-2' : 'grid-cols-1'])>
        @if ($nova)
            <x-ai.nova-shell :nova="$nova" compact enable-wake-word class="min-w-0">
                <x-ai.assistant-card
                    :assistant-name="$nova['assistant_name']"
                    :assistant-url="$nova['assistant_url'] ?? null"
                    compact
                />
            </x-ai.nova-shell>
        @endif

        @if ($hasCalendar)
            <x-calendar-preview-panel :preview="$flowdeskCalendarNav" :compact="true" :show-upcoming="false" />
        @endif
    </div>
@endif

<div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-flow.stat-card :label="__('Clients')" variant="indigo">
        {{ number_format($metrics['clients_count'] ?? 0) }}
    </x-flow.stat-card>
    <x-flow.stat-card :label="__('Projects')" variant="cyan">
        {{ number_format($metrics['projects_count'] ?? 0) }}
    </x-flow.stat-card>
    <x-flow.stat-card :label="__('Open invoices')" variant="emerald">
        {{ number_format($metrics['open_invoices_count'] ?? 0) }}
    </x-flow.stat-card>
    <x-flow.stat-card :label="__('Outstanding')" variant="amber">
        @forelse ($byCurrency as $cur => $amt)
            <span @class(['tabular-nums', 'block text-xl sm:text-2xl' => $loop->first, 'mt-1 block text-base font-semibold sm:text-lg' => ! $loop->first])>
                {{ flowdesk_format_minor((int) $amt, $cur) }} {{ $cur }}
            </span>
        @empty
            @php $fallbackCur = flowdesk_normalize_currency_code($metrics['currency'] ?? 'USD'); @endphp
            <span class="tabular-nums">{{ flowdesk_format_minor(0, $fallbackCur) }} {{ $fallbackCur }}</span>
        @endforelse
        <p class="mt-2 text-xs font-normal text-slate-600 dark:text-slate-400">{{ __('Paid invoices') }}: {{ number_format($metrics['paid_invoices_count'] ?? 0) }}</p>
    </x-flow.stat-card>
</div>
