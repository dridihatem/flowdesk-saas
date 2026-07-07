@php
    $currency = $kpis['currency'] ?? 'USD';
    $chartPayload = [
        'labels' => $series['labels'],
        'counts' => $series['payment_counts'],
        'paidMinor' => $series['payment_amounts_minor'],
        'paymentsByChannel' => $paymentsByChannel,
        'minorScale' => flowdesk_currency_minor_scale($currency),
    ];
    $tabQuery = array_filter([
        'from' => $from->format('Y-m-d'),
        'to' => $to->format('Y-m-d'),
    ]);
@endphp

<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Analytics')"
                :description="__('Explore trends, filter by date, and break down revenue and providers.')"
            />

            <nav class="mt-8 flex flex-wrap gap-2 rounded-2xl border border-slate-200/80 bg-white/60 p-2 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                @foreach ([
                    'overview' => __('Overview'),
                    'daterange' => __('By date'),
                    'providers' => __('By provider'),
                    'revenue' => __('By revenue'),
                ] as $key => $label)
                    <a
                        href="{{ route('analytics.index', array_merge($tabQuery, ['report' => $key])) }}"
                        @class([
                            'rounded-xl px-4 py-2 text-sm font-semibold transition',
                            'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' => $report === $key,
                            'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $report !== $key,
                        ])
                    >{{ $label }}</a>
                @endforeach
            </nav>

            @if (in_array($report, ['daterange', 'revenue'], true))
                <form method="GET" action="{{ route('analytics.index') }}" class="mt-6 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200/80 bg-white/70 p-4 dark:border-slate-700/80 dark:bg-slate-900/50">
                    <input type="hidden" name="report" value="{{ $report }}" />
                    <div>
                        <x-input-label for="analytics_from" :value="__('From')" />
                        <x-text-input id="analytics_from" name="from" type="date" class="mt-1 block" :value="$from->format('Y-m-d')" />
                    </div>
                    <div>
                        <x-input-label for="analytics_to" :value="__('To')" />
                        <x-text-input id="analytics_to" name="to" type="date" class="mt-1 block" :value="$to->format('Y-m-d')" />
                    </div>
                    <x-primary-button type="submit">{{ __('Apply range') }}</x-primary-button>
                </form>
            @endif

            <div class="mt-8 space-y-8">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-flow.stat-card :label="__('Clients')" variant="indigo">
                        {{ number_format($kpis['clients_count'] ?? 0) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Projects')" variant="cyan">
                        {{ number_format($kpis['projects_count'] ?? 0) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Providers')" variant="emerald">
                        {{ number_format($commission['provider_count'] ?? 0) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Avg. commission rate')" variant="amber">
                        @if (($commission['average_commission_rate'] ?? null) !== null)
                            {{ number_format((float) $commission['average_commission_rate'] * 100, 2) }}%
                        @else
                            —
                        @endif
                    </x-flow.stat-card>
                </div>

                @if ($report === 'overview')
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Projects by source') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            {{ __('How projects were created or attributed (website form, business provider, inquiry, or manual).') }}
                        </p>
                        <dl class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach (\App\Enums\ProjectSource::cases() as $src)
                                <div class="rounded-xl border border-slate-200/70 bg-white/50 px-4 py-3 dark:border-slate-600/70 dark:bg-slate-800/40">
                                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $src->label() }}</dt>
                                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($projectSources['by_source'][$src->value] ?? 0) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">
                            {{ __('Total projects') }}: <span class="font-semibold text-slate-900 dark:text-white">{{ number_format($projectSources['total'] ?? 0) }}</span>
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Commission context') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            {{ __('Open invoice volume') }}:
                            <span class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ flowdesk_format_minor((int) ($commission['open_invoice_volume_minor'] ?? 0), $currency) }}</span>
                            {{ $currency }}
                        </p>
                    </div>

                    <div
                        id="flowdesk-analytics-root"
                        class="space-y-8"
                        data-chart='@json($chartPayload)'
                        data-label-invoices="{{ __('Completed payments') }}"
                        data-label-paid="{{ __('Payment volume') }}"
                        data-label-channel="{{ __('By payment channel') }}"
                    >
                        <div class="grid gap-6 lg:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                                <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payments per month') }}</h3>
                                <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">{{ __('Recorded payment transactions only (not invoice drafts).') }}</p>
                                <div class="h-72 w-full">
                                    <canvas id="chart-invoices"></canvas>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                                <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payment volume trend') }}</h3>
                                <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">{{ __('Completed payment totals by month.') }}</p>
                                <div class="h-72 w-full">
                                    <canvas id="chart-revenue"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                            <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payments by channel') }}</h3>
                            <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">{{ __('Share of completed payment volume (Stripe, PayPal, manual, etc.).') }}</p>
                            <div class="h-72 w-full max-w-md">
                                <canvas id="chart-payments-channel"></canvas>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($report === 'daterange')
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Invoices by day') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Counts and amounts created in the selected range.') }}</p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead>
                                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="py-3 pr-4 text-start">{{ __('Date') }}</th>
                                        <th class="py-3 pr-4 text-start">{{ __('Invoices') }}</th>
                                        <th class="py-3 text-start">{{ __('Amount') }} ({{ $currency }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($dailyTotals as $row)
                                        <tr>
                                            <td class="py-3 pr-4 text-slate-900 dark:text-white text-start">{{ $row['date'] }}</td>
                                            <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['count']) }}</span></td>
                                            <td class="py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row['amount_minor'], $currency) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-8 text-center text-slate-500 dark:text-slate-400">{{ __('No invoices in this range.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($report === 'providers')
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Provider performance') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Projects linked to each business provider and default commission rate.') }}</p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead>
                                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="py-3 pr-4 text-start">{{ __('Provider') }}</th>
                                        <th class="py-3 pr-4 text-start">{{ __('Email') }}</th>
                                        <th class="py-3 pr-4 text-start">{{ __('Projects') }}</th>
                                        <th class="py-3 text-start">{{ __('Commission') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($providerRows as $p)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-slate-900 dark:text-white text-start">{{ $p['name'] }}</td>
                                            <td class="py-3 pr-4 text-slate-600 dark:text-slate-300 text-start">{{ $p['email'] ?? '—' }}</td>
                                            <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($p['projects_count']) }}</span></td>
                                            <td class="py-3 text-end">
                                                @if ($p['commission_rate'] !== null)
                                                    <span class="flowdesk-ltr-num tabular-nums">{{ number_format($p['commission_rate'] * 100, 2) }}%</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">{{ __('No providers yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($report === 'revenue')
                    @php
                        $totalCount = array_sum(array_column($revenueByStatus, 'count'));
                        $totalMinor = array_sum(array_column($revenueByStatus, 'amount_minor'));
                    @endphp
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Revenue by invoice status') }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Totals for invoices created in the selected date range.') }}</p>
                        <p class="mt-3 text-sm font-medium text-slate-800 dark:text-slate-200">
                            {{ __('All statuses') }}: {{ number_format($totalCount) }} {{ __('invoices') }} ·
                            {{ flowdesk_format_minor((int) $totalMinor, $currency) }} {{ $currency }}
                        </p>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead>
                                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="py-3 pr-4 text-start">{{ __('Status') }}</th>
                                        <th class="py-3 pr-4 text-start">{{ __('Invoices') }}</th>
                                        <th class="py-3 text-start">{{ __('Amount') }} ({{ $currency }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($revenueByStatus as $statusKey => $row)
                                        @php
                                            $statusLabel = \App\Enums\InvoiceStatus::tryFrom($statusKey)?->label() ?? $statusKey;
                                        @endphp
                                        <tr>
                                            <td class="py-3 pr-4 text-slate-900 dark:text-white text-start">{{ $statusLabel }}</td>
                                            <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['count']) }}</span></td>
                                            <td class="py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row['amount_minor'], $currency) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-8 text-center text-slate-500 dark:text-slate-400">{{ __('No invoices in this range.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-lg shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Daily volume (same range)') }}</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                <thead>
                                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <th class="py-3 pr-4 text-start">{{ __('Date') }}</th>
                                        <th class="py-3 pr-4 text-start">{{ __('Invoices') }}</th>
                                        <th class="py-3 text-start">{{ __('Amount') }} ({{ $currency }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($dailyTotals as $row)
                                        <tr>
                                            <td class="py-3 pr-4 text-slate-900 dark:text-white text-start">{{ $row['date'] }}</td>
                                            <td class="py-3 pr-4 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['count']) }}</span></td>
                                            <td class="py-3 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row['amount_minor'], $currency) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-8 text-center text-slate-500 dark:text-slate-400">{{ __('No invoices in this range.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/analytics.js')
    @endpush
</x-app-layout>
