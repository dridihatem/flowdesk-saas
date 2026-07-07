@php
    $planRows = $planRows ?? [];
    $displayCurrency = $displayCurrency ?? 'USD';
    $supportedCurrencies = $supportedCurrencies ?? ['USD'];
    $currencyLabels = (isset($currencyLabels) && is_array($currencyLabels)) ? $currencyLabels : [];
    $formAction = $formAction ?? url()->current();
    $corporate = $corporate ?? false;
    $labeledFeatures = $labeledFeatures ?? false;
    $planLimitService = $labeledFeatures ? app(\App\Services\PlanLimitService::class) : null;
    $periodInitial = (int) request('period', 3);
    if (! in_array($periodInitial, [3, 6, 12], true)) {
        $periodInitial = 3;
    }
    $activeTab = $corporate
        ? 'border-slate-900 bg-slate-900 text-white'
        : 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-600/25';
    $inactiveTab = $corporate
        ? 'border-slate-200 bg-white text-slate-700 hover:border-slate-300'
        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-slate-500';
    $panelClass = $corporate ? 'rounded-lg border border-slate-200 bg-white flex flex-col p-6' : 'flow-panel flex flex-col p-6';
    $toolbarClass = $corporate
        ? 'rounded-lg border border-slate-200 bg-white p-4'
        : 'rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50';
@endphp

<div
    class="space-y-6"
    x-data="{
        period: {{ $periodInitial }},
        setPeriod(m) {
            this.period = m;
            const u = new URL(window.location.href);
            u.searchParams.set('period', String(m));
            window.history.replaceState({}, '', u.toString());
        }
    }"
>
    <div class="{{ $toolbarClass }}">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Billing period') }}</p>
                <div class="mt-3 flex flex-wrap gap-2" role="tablist" aria-label="{{ __('Billing period') }}">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="period === 3"
                        @click="setPeriod(3)"
                        :class="period === 3 ? '{{ $activeTab }}' : '{{ $inactiveTab }}'"
                        class="rounded-md border px-4 py-2.5 text-sm font-semibold transition"
                    >
                        {{ __('3 months') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="period === 6"
                        @click="setPeriod(6)"
                        :class="period === 6 ? '{{ $activeTab }}' : '{{ $inactiveTab }}'"
                        class="rounded-md border px-4 py-2.5 text-sm font-semibold transition"
                    >
                        {{ __('6 months') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="period === 12"
                        @click="setPeriod(12)"
                        :class="period === 12 ? '{{ $activeTab }}' : '{{ $inactiveTab }}'"
                        class="rounded-md border px-4 py-2.5 text-sm font-semibold transition"
                    >
                        {{ __('1 year') }}
                    </button>
                </div>
            </div>
            <div class="flex min-w-0 flex-col gap-2 border-t border-slate-200/80 pt-4 lg:w-80 lg:border-s-0 lg:border-t-0 lg:ps-6 lg:pt-0 dark:border-slate-700/80">
                <label for="plan_pricing_currency" class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Display currency') }}</label>
                <form method="GET" action="{{ $formAction }}" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="period" :value="period" />
                    <select
                        id="plan_pricing_currency"
                        name="currency"
                        class="flow-input-select max-w-full rounded-xl border-slate-200 text-sm dark:border-slate-600"
                        onchange="this.form.submit()"
                    >
                        @foreach ($supportedCurrencies as $code)
                            <option value="{{ $code }}" @selected($displayCurrency === $code)>
                                {{ $code }}
                            </option>
                        @endforeach
                    </select>
                </form>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Converted from each plan\'s billing currency using platform rates (indicative).') }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($planRows as $row)
            @php($p = $row['plan'])
            @php($periods = $row['periods'])
            <div class="{{ $panelClass }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $p->name }}</h3>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $p->slug }}</p>
                    </div>
                    <span class="shrink-0 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $p->currency }}</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ __('Reference monthly') }}: <span class="font-semibold tabular-nums text-slate-800 dark:text-slate-200">{{ number_format((float) $p->price_monthly, 2) }} {{ $p->currency }}</span></p>

                @php($periodFormats = [3 => $periods[3]['formatted'], 6 => $periods[6]['formatted'], 12 => $periods[12]['formatted']])
                <div class="mt-6 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Total for selected period') }}</p>
                    <p
                        class="mt-2 text-3xl font-bold tabular-nums text-slate-900 dark:text-white"
                        x-text="({{ json_encode($periodFormats) }})[period] || '—'"
                    >
                        {{ $periodFormats[$periodInitial] }}
                    </p>
                </div>

                @php($featureRows = $labeledFeatures ? $planLimitService->summarizePlanFeatures($p) : [])
                @if ($labeledFeatures && count($featureRows) > 0)
                    <div class="mt-4 border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Plan features & limits') }}</p>
                        <div class="mt-2">
                            @include('partials.plan-feature-list', ['featureRows' => $featureRows, 'compact' => true])
                        </div>
                    </div>
                @elseif ($p->limits->isNotEmpty())
                    <div class="mt-4 border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Highlights') }}</p>
                        <ul class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-400">
                            @foreach ($p->limits->take(6) as $lim)
                                <li><span class="font-mono">{{ $lim->feature_key }}</span>: {{ $lim->limit_value ?? __('Unlimited') }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
