@props(['metrics' => [], 'dashboardChart' => []])
@php
    $chart = is_array($dashboardChart) ? $dashboardChart : [];
    $chartPayload = [
        'labels' => $chart['labels'] ?? [],
        'counts' => $chart['counts'] ?? [],
        'paidMinor' => $chart['paidMinor'] ?? [],
        'paymentsByChannel' => $chart['paymentsByChannel'] ?? ['labels' => [], 'amounts_minor' => []],
        'minorScale' => (int) ($chart['minorScale'] ?? 100),
    ];
@endphp
<div
    id="flowdesk-dashboard-charts-root"
    class="space-y-6"
    data-chart='@json($chartPayload)'
    data-label-invoices="{{ __('Completed payments') }}"
    data-label-paid="{{ __('Payment volume') }}"
    data-label-channel="{{ __('By channel') }}"
>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payments per month') }}</h3>
                <a href="{{ route('analytics.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Analytics') }} →</a>
            </div>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Last six months — completed payments only.') }}</p>
            <div class="mt-4 h-64 w-full">
                <canvas id="chart-dashboard-invoices"></canvas>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payment volume') }}</h3>
                <a href="{{ route('analytics.index', ['report' => 'revenue']) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Revenue report') }} →</a>
            </div>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Totals from completed payment records.') }}</p>
            <div class="mt-4 h-64 w-full">
                <canvas id="chart-dashboard-revenue"></canvas>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10 lg:col-span-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payments by channel') }}</h3>
                <a href="{{ route('reports.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Reports') }} →</a>
            </div>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('All-time share by gateway (payments only).') }}</p>
            <div class="mt-4 h-64 w-full max-w-sm">
                <canvas id="chart-dashboard-payments-channel"></canvas>
            </div>
        </div>
    </div>
</div>
