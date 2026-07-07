@php
    $currency = $kpis['currency'] ?? 'USD';
    $exportQuery = ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    $analyticsSvc = app(\App\Services\AnalyticsService::class);
@endphp

<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Reports')"
                :description="__('Summaries and CSV exports for your workspace in a date range. Totals below are all-time; tables use the selected range.')"
            />

            @if ($errors->has('export'))
                <div class="mt-8 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/50 dark:text-amber-100">
                    {{ $errors->first('export') }}
                </div>
            @endif

            <form method="GET" action="{{ route('reports.index') }}" class="mt-8 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200/80 bg-white/70 p-4 dark:border-slate-700/80 dark:bg-slate-900/50">
                <div>
                    <x-input-label for="reports_from" :value="__('From')" />
                    <x-text-input id="reports_from" name="from" type="date" class="mt-1 block" :value="$from->format('Y-m-d')" />
                </div>
                <div>
                    <x-input-label for="reports_to" :value="__('To')" />
                    <x-text-input id="reports_to" name="to" type="date" class="mt-1 block" :value="$to->format('Y-m-d')" />
                </div>
                <x-primary-button type="submit">{{ __('Apply range') }}</x-primary-button>
                <div class="flex flex-wrap gap-2 ms-auto">
                    <a href="{{ route('reports.export', array_merge($exportQuery, ['type' => 'invoices'])) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        {{ __('Export invoices CSV') }}
                    </a>
                    <a href="{{ route('reports.export', array_merge($exportQuery, ['type' => 'invoices-pdf'])) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="fa-regular fa-file-pdf text-red-600 dark:text-red-400" aria-hidden="true"></i>
                        {{ __('Export invoices PDF (ZIP)') }}
                    </a>
                    <a href="{{ route('reports.export', array_merge($exportQuery, ['type' => 'projects'])) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        {{ __('Export projects CSV') }}
                    </a>
                </div>
            </form>

            @if ($aiCounselAvailable ?? false)
                <div
                    class="mt-8 rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/80 via-white to-white p-6 dark:border-indigo-500/30 dark:from-indigo-950/40 dark:via-slate-900/50 dark:to-slate-900/50"
                    x-data="reportAiCounsel({
                        counselUrl: @js(route('reports.ai-counsel')),
                        pdfUrl: @js(route('reports.ai-counsel.pdf')),
                        from: @js($from->format('Y-m-d')),
                        to: @js($to->format('Y-m-d')),
                    })"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('AI report counsel') }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">{{ __('AI report counsel lead') }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-semibold text-indigo-800 dark:border-indigo-500/40 dark:bg-indigo-950/50 dark:text-indigo-200">
                            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                            {{ __('Included in plan') }}
                        </span>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-50"
                            x-on:click="generate()"
                            x-bind:disabled="busy"
                        >
                            <i class="fa-solid fa-lightbulb text-xs" aria-hidden="true"></i>
                            <span x-text="busy ? @js(__('Working…')) : @js(__('Get AI counsel')) + ' ({{ $aiCounselCost }} {{ __('credits') }})'"></span>
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                            x-on:click="exportPdf()"
                            x-bind:disabled="!counsel || pdfBusy"
                            x-show="counsel"
                            x-cloak
                        >
                            <i class="fa-regular fa-file-pdf text-red-600" aria-hidden="true"></i>
                            <span x-text="pdfBusy ? @js(__('Working…')) : @js(__('Export counsel to PDF'))"></span>
                        </button>
                    </div>
                    <p x-show="error" x-cloak class="mt-4 text-sm text-rose-600 dark:text-rose-400" x-text="error"></p>
                    <div x-show="counsel" x-cloak class="mt-5 rounded-xl border border-slate-200/80 bg-white/90 p-5 dark:border-slate-700 dark:bg-slate-900/80">
                        <pre class="max-h-96 overflow-auto whitespace-pre-wrap text-sm text-slate-800 dark:text-slate-200" x-text="counsel"></pre>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('AI-generated content — review before sharing with clients.') }}</p>
                    </div>
                </div>
            @endif

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-flow.stat-card :label="__('Clients')" variant="indigo">
                    {{ number_format($kpis['clients_count'] ?? 0) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('Projects')" variant="cyan">
                    {{ number_format($kpis['projects_count'] ?? 0) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('Open invoices')" variant="amber">
                    {{ number_format($kpis['open_invoices_count'] ?? 0) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('Paid invoices')" variant="emerald">
                    {{ number_format($kpis['paid_invoices_count'] ?? 0) }}
                </x-flow.stat-card>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/80 dark:bg-slate-900/50">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('In selected range') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Total invoice amount') }}:</p>
                @if (count($invoiceTotalsByCurrency) > 0)
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($invoiceTotalsByCurrency as $row)
                            <li class="flex flex-wrap items-baseline gap-x-2 text-sm">
                                <span class="font-mono text-base font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ flowdesk_format_minor((int) $row['total_minor'], $row['currency']) }}</span>
                                <span class="font-semibold uppercase text-slate-700 dark:text-slate-300">{{ $row['currency'] }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">({{ number_format($row['count']) }} {{ __('invoices') }})</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">0</p>
                @endif
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/80 dark:bg-slate-900/50">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Completed payments (selected range)') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                    {{ __('Payment records only — not invoice documents.') }}
                    {{ __('Total received') }}:
                    <span class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ flowdesk_format_minor((int) $paymentsTotalMinorInRange, $currency) }}</span>
                    {{ $currency }}
                    · {{ __('Transactions') }}: <strong>{{ number_format($paymentsForRange->count()) }}</strong>
                </p>
                @if (count($channelTotals) > 0)
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 text-sm dark:divide-slate-700/80">
                            <thead class="bg-slate-50 text-start text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/80">
                                <tr>
                                    <th class="px-4 py-2 text-start">{{ __('Payment channel') }}</th>
                                    <th class="px-4 py-2 text-start">{{ __('Count') }}</th>
                                    <th class="px-4 py-2 text-end">{{ __('Amount') }} ({{ $currency }})</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($channelTotals as $chKey => $row)
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-slate-900 dark:text-white text-start">{{ $analyticsSvc->paymentChannelLabel($chKey) }}</td>
                                        <td class="px-4 py-2 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row['count']) }}</span></td>
                                        <td class="px-4 py-2 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row['amount_minor'], $currency) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('No completed payments in this range.') }}</p>
                @endif
            </div>

            <div class="mt-8 flow-panel overflow-hidden p-0">
                <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Payments in range') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Completed payment rows, up to 200') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 text-sm dark:divide-slate-700/80">
                        <thead class="bg-slate-50 text-start text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Channel') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Invoice') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Received') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/70">
                            @forelse ($paymentsInRange as $pay)
                                <tr>
                                    <td class="px-4 py-3 text-start">{{ $analyticsSvc->paymentChannelLabel(strtolower(trim((string) ($pay->provider ?? ''))) ?: 'other') }}</td>
                                    <td class="px-4 py-3 text-start">
                                        @if ($pay->invoice)
                                            <a href="{{ route('invoices.show', $pay->invoice) }}" class="font-mono text-indigo-600 hover:underline dark:text-indigo-400">{{ $pay->invoice->number ?? $pay->invoice->id }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $pay->amount, $pay->currency) }} {{ $pay->currency }}</span></td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $pay->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No payments in this range.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/80 dark:bg-slate-900/50">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Projects by source (all time)') }}</h3>
                    <dl class="mt-4 space-y-2 text-sm">
                        @foreach (\App\Enums\ProjectSource::cases() as $src)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600 dark:text-slate-400">{{ $src->label() }}</dt>
                                <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($projectSources['by_source'][$src->value] ?? 0) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-slate-700/80 dark:bg-slate-900/50">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Commission context') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Providers') }}: <strong>{{ number_format($commission['provider_count'] ?? 0) }}</strong>
                        @if (($commission['average_commission_rate'] ?? null) !== null)
                            · {{ __('Avg. rate') }}: <strong>{{ number_format((float) $commission['average_commission_rate'] * 100, 2) }}%</strong>
                        @endif
                    </p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        {{ __('Open invoice volume') }}:
                        <span class="font-mono font-semibold">{{ flowdesk_format_minor((int) ($commission['open_invoice_volume_minor'] ?? 0), $currency) }}</span>
                        {{ $currency }}
                        <span class="text-xs text-slate-500 dark:text-slate-400">({{ __('indicative if multiple currencies') }})</span>
                    </p>
                </div>
            </div>

            <div class="mt-8 flow-panel overflow-hidden p-0">
                <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Invoices in range') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Up to 200 rows, newest first') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 text-sm dark:divide-slate-700/80">
                        <thead class="bg-slate-50 text-start text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Invoice #') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Client') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Amount') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/70">
                            @forelse ($invoicesInRange as $inv)
                                <tr>
                                    <td class="px-4 py-3 text-start">
                                        <a href="{{ route('invoices.show', $inv) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $inv->number ?? $inv->id }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-start">{{ $inv->client?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-start">{{ $inv->status->label() }}</td>
                                    <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $inv->amount, $inv->currency) }} {{ $inv->currency }}</span></td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $inv->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No invoices in this range.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-8 flow-panel overflow-hidden p-0">
                <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Projects created in range') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Up to 200 rows, newest first') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 text-sm dark:divide-slate-700/80">
                        <thead class="bg-slate-50 text-start text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Title') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Client') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/70">
                            @forelse ($projectsInRange as $proj)
                                <tr>
                                    <td class="px-4 py-3 text-start">
                                        <a href="{{ route('projects.show', $proj) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ $proj->title }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-start">{{ $proj->status->label() }}</td>
                                    <td class="px-4 py-3 text-start">{{ $proj->client?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $proj->created_at?->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No projects in this range.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="mt-8 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('analytics.index') }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Open analytics for charts and deeper breakdowns') }} →</a>
            </p>
        </div>
    </div>

    @if ($aiCounselAvailable ?? false)
        @push('scripts')
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('reportAiCounsel', (config) => ({
                        counselUrl: config.counselUrl,
                        pdfUrl: config.pdfUrl,
                        from: config.from,
                        to: config.to,
                        busy: false,
                        pdfBusy: false,
                        error: '',
                        counsel: '',
                        async generate() {
                            this.error = '';
                            this.busy = true;
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            try {
                                const res = await fetch(this.counselUrl, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': token,
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({ from: this.from, to: this.to }),
                                });
                                const data = await res.json().catch(() => ({}));
                                if (!res.ok) {
                                    this.error = data.message || @json(__('Something went wrong.'));
                                    return;
                                }
                                this.counsel = data.suggestion || '';
                                if (data.from) this.from = data.from;
                                if (data.to) this.to = data.to;
                            } catch (e) {
                                this.error = @json(__('Network error.'));
                            } finally {
                                this.busy = false;
                            }
                        },
                        async exportPdf() {
                            if (!this.counsel) return;
                            this.pdfBusy = true;
                            this.error = '';
                            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = this.pdfUrl;
                            form.target = '_blank';
                            const fields = {
                                _token: token,
                                counsel: this.counsel,
                                from: this.from,
                                to: this.to,
                            };
                            Object.entries(fields).forEach(([name, value]) => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = name;
                                input.value = value;
                                form.appendChild(input);
                            });
                            document.body.appendChild(form);
                            form.submit();
                            form.remove();
                            this.pdfBusy = false;
                        },
                    }));
                });
            </script>
        @endpush
    @endif
</x-app-layout>
