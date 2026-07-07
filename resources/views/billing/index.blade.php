@php
    $aiCreditRow = collect($subscriptionFeatureRows ?? [])->firstWhere('key', 'ai_credits');
    $aiCreditLimit = $aiCreditRow['limit'] ?? null;
    $aiCreditPct = ($aiCreditLimit && (int) $aiCreditLimit > 0)
        ? min(100, (int) round(((int) $aiCreditsUsed / (int) $aiCreditLimit) * 100))
        : null;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Billing & monetization') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl w-full sm:px-6 lg:px-8 space-y-8">
            <x-flow.page-header
                :title="__('Billing & monetization')"
                :description="__('billing_page_intro')"
            />

            @if ($errors->any())
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Current subscription --}}
            <div class="flow-panel overflow-hidden p-0">
                <div class="border-b border-slate-200/80 bg-slate-50/80 px-8 py-5 dark:border-slate-700 dark:bg-slate-800/40">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Subscription') }}</h3>
                            @if ($plan)
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Plan') }}: <strong class="text-slate-900 dark:text-white">{{ $plan->name }}</strong></p>
                            @endif
                        </div>
                        @if ($subscription?->status)
                            <x-flow.badge :variant="$subscription->status === 'active' ? 'success' : 'neutral'">
                                {{ \Illuminate\Support\Str::headline($subscription->status) }}
                            </x-flow.badge>
                        @endif
                    </div>
                </div>

                <div class="p-8">
                    @if ($plan)
                        <div class="grid gap-6 lg:grid-cols-3">
                            <div class="lg:col-span-1">
                                <p class="text-3xl font-bold tabular-nums text-slate-900 dark:text-white">
                                    {{ number_format((float) $plan->price_monthly, 0) }}
                                    <span class="text-base font-semibold text-slate-500">{{ $plan->currency }}</span>
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Monthly') }}</p>
                                <p class="mt-2 font-mono text-xs text-slate-400">{{ $plan->slug }}</p>
                            </div>
                            <div class="lg:col-span-2 grid gap-3 sm:grid-cols-2">
                                @if ($subscription?->trial_ends_at)
                                    <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Trial ends') }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $subscription->trial_ends_at->format('Y-m-d') }}</p>
                                    </div>
                                @endif
                                @if ($subscription?->current_period_end)
                                    <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Current period ends') }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ $subscription->current_period_end->format('Y-m-d') }}</p>
                                    </div>
                                @endif
                                @if ($aiCreditPct !== null)
                                    <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 sm:col-span-2 dark:border-slate-700 dark:bg-slate-900/50">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('AI credits (this month)') }}</p>
                                            <p class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                                {{ __('billing_ai_credits_used', ['used' => number_format((int) $aiCreditsUsed), 'limit' => number_format((int) $aiCreditLimit)]) }}
                                            </p>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                            <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $aiCreditPct }}%"></div>
                                        </div>
                                    </div>
                                @elseif ($aiCreditsUsed > 0)
                                    <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 sm:col-span-2 dark:border-slate-700 dark:bg-slate-900/50">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('AI credits (this month)') }}</p>
                                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                                            {{ __('billing_ai_credits_unlimited', ['used' => number_format((int) $aiCreditsUsed)]) }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if (! empty($subscriptionFeatureRows))
                            <div class="mt-8 rounded-xl border border-slate-200/80 bg-slate-50/50 p-5 dark:border-slate-700 dark:bg-slate-800/30">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Plan features & limits') }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Included modules and usage against your current plan.') }}</p>
                                <div class="mt-4">
                                    @include('partials.plan-feature-list', ['featureRows' => $subscriptionFeatureRows])
                                </div>
                            </div>
                        @endif

                        @if ($stripePortalAvailable && $subscription)
                            <form method="POST" action="{{ route('billing.stripe-portal') }}" class="mt-6 flex flex-wrap items-center gap-4">
                                @csrf
                                <x-secondary-button type="submit" class="inline-flex items-center gap-2">
                                    <i class="fa-brands fa-stripe text-sm" aria-hidden="true"></i>
                                    {{ __('Stripe Customer Portal') }}
                                </x-secondary-button>
                                <p class="text-xs text-slate-500">{{ __('Manage payment methods and subscription in Stripe (requires STRIPE_SECRET).') }}</p>
                            </form>
                        @endif

                        @if (count($planAddons) > 0)
                            <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white/60 p-5 dark:border-slate-600 dark:bg-slate-900/30">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Plan add-ons') }}</p>
                                <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                    @foreach ($planAddons as $addon)
                                        <li class="flex justify-between gap-4 border-b border-slate-100 pb-2 last:border-0 dark:border-slate-800">
                                            <span>{{ $addon['name'] ?? '—' }}</span>
                                            @if (isset($addon['price_monthly_minor']))
                                                <span class="tabular-nums font-medium text-slate-800 dark:text-slate-200">{{ flowdesk_format_minor((int) ($addon['price_monthly_minor'] ?? 0), $addon['currency'] ?? $plan->currency) }} {{ $addon['currency'] ?? $plan->currency }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-3 text-xs text-slate-500">{{ __('Contact sales to activate add-ons; prices are indicative.') }}</p>
                            </div>
                        @endif
                    @else
                        <p class="text-slate-600 dark:text-slate-400">{{ __('No active subscription on file.') }}</p>
                    @endif
                </div>
            </div>

            {{-- Usage stats --}}
            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('billing_usage_overview') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-flow.stat-card :label="__('Paid revenue (30d)')" variant="emerald">
                        {{ flowdesk_format_minor((int) $invoiceRevenue, $displayCurrency) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Commission (12mo)')" variant="amber">
                        {{ flowdesk_format_minor((int) ($commissionTotal ?? 0), $displayCurrency) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('Open proposals')" variant="cyan">
                        {{ number_format((int) $proposalPipeline) }}
                    </x-flow.stat-card>
                    <x-flow.stat-card :label="__('AI credits (this month)')" variant="indigo">
                        {{ number_format((int) $aiCreditsUsed) }}
                    </x-flow.stat-card>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <x-flow.stat-card :label="__('Form submissions (this month)')" variant="cyan">
                        {{ number_format((int) $formSubmissionsMonth) }}
                    </x-flow.stat-card>
                    <div class="flow-panel p-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Estimated pay-per-use (AI)') }}</p>
                        <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white sm:text-3xl">{{ flowdesk_format_minor((int) $payPerUseEstimate, $displayCurrency) }}</p>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('ai_pay_per_use_hint', ['rate' => $aiCreditPriceMinor]) }}</p>
                    </div>
                </div>
            </div>

            @if ($aiGrowthAvailable && ! empty($aiGrowthModules))
                <div
                    class="flow-panel p-8"
                    x-data="billingGrowthAdvisor({ suggestUrl: @js(route('assistant.suggest')), modules: @js($aiGrowthModules) })"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('AI growth advisor') }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">{{ __('AI growth advisor lead') }}</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-800 dark:border-indigo-500/40 dark:bg-indigo-950/50 dark:text-indigo-200">
                            <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                            {{ __('Included in plan') }}
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-3">
                        <template x-for="mod in modules" :key="mod.mode">
                            <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    <i class="fa-solid" :class="mod.icon" aria-hidden="true"></i>
                                </div>
                                <h4 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white" x-text="mod.title"></h4>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400" x-text="mod.description"></p>
                                <button
                                    type="button"
                                    class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800 transition hover:bg-indigo-100 disabled:opacity-50 dark:border-indigo-500/40 dark:bg-indigo-950/50 dark:text-indigo-200 dark:hover:bg-indigo-900/40"
                                    x-on:click="run(mod)"
                                    x-bind:disabled="busy"
                                >
                                    <i class="fa-solid fa-lightbulb text-[11px]" aria-hidden="true"></i>
                                    <span x-text="busy && activeMode === mod.mode ? @js(__('Working…')) : (@js(__('Get AI suggestions')) + ' (' + mod.credit_cost + ' ' + @js(__('credits')) + ')')"></span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="error" x-cloak class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-200" x-text="error"></div>

                    <div x-show="result" x-cloak class="mt-6 rounded-xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-800/40">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white" x-text="resultTitle"></p>
                            <button type="button" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" x-on:click="copy()">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                                {{ __('Copy') }}
                            </button>
                        </div>
                        <pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-white p-4 text-sm text-slate-800 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700" x-text="result"></pre>
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('AI-generated content — review before sharing with clients.') }}</p>
                    </div>
                </div>
            @endif

            @if (! empty($planPricingRows))
                <div class="flow-panel p-8">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Plans & periods') }}</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Totals per billing period (reference). Switch currency to compare.') }}</p>
                    <div class="mt-6">
                        @include('partials.plan-pricing', [
                            'planRows' => $planPricingRows,
                            'displayCurrency' => $displayCurrency,
                            'supportedCurrencies' => $supported,
                            'currencyLabels' => $currencyLabels,
                            'formAction' => route('billing.index'),
                            'labeledFeatures' => true,
                        ])
                    </div>
                </div>
            @endif

            <div>
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('billing_quick_settings') }}</h3>
                <div class="grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('settings.smtp') }}" class="flow-panel flex items-center gap-3 p-4 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('SMTP settings') }}</span>
                    </a>
                    <a href="{{ route('settings.invoice-documents') }}" class="flow-panel flex items-center gap-3 p-4 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Invoice documents') }}</span>
                    </a>
                    <a href="{{ route('settings.security') }}" class="flow-panel flex items-center gap-3 p-4 transition hover:border-indigo-300 dark:hover:border-indigo-600/50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        </span>
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Security') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('billingGrowthAdvisor', (config) => ({
                    modules: config.modules || [],
                    suggestUrl: config.suggestUrl,
                    busy: false,
                    activeMode: null,
                    error: '',
                    result: '',
                    resultTitle: '',
                    async run(mod) {
                        this.error = '';
                        this.result = '';
                        this.busy = true;
                        this.activeMode = mod.mode;
                        this.resultTitle = mod.title;
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        try {
                            const res = await fetch(this.suggestUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ mode: mod.mode, context: mod.context }),
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                this.error = data.message || @json(__('Something went wrong.'));
                                return;
                            }
                            this.result = data.suggestion || '';
                        } catch (e) {
                            this.error = @json(__('Network error.'));
                        } finally {
                            this.busy = false;
                            this.activeMode = null;
                        }
                    },
                    copy() {
                        navigator.clipboard?.writeText(this.result || '');
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
