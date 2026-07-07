<x-admin-layout>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <x-flow.page-header
            :title="__('Subscription plans')"
            :description="__('Pricing and identifiers for workspace subscriptions. Feature limits are defined per plan (see database or future admin UI).')"
        />

        <a
            href="{{ route('admin.plans.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
            title="{{ __('Create plan') }}"
            aria-label="{{ __('Create plan') }}"
        >
            <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
            <span>{{ __('New plan') }}</span>
        </a>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($plans as $plan)
            <div class="flow-panel p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <h3 class="truncate text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                            <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-xs font-semibold text-slate-600">
                                {{ $plan->currency }}
                            </span>
                        </div>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $plan->slug }}</p>
                        <p class="mt-3 text-sm font-semibold text-slate-900">
                            {{ $plan->price_monthly}}
                            <span class="text-slate-500">{{ $plan->currency }} / {{ __('month') }}</span>
                        </p>
                        @if ($plan->periodPrices->isNotEmpty())
                            <ul class="mt-3 space-y-1 text-xs text-slate-600">
                                @foreach ($plan->periodPrices as $pp)
                                    <li class="flex justify-between gap-2">
                                        <span>{{ match ($pp->period_months) {
                                            3 => __('3 months'),
                                            6 => __('6 months'),
                                            12 => __('1 year'),
                                            default => $pp->period_months.' '.__('months'),
                                        } }}</span>
                                        <span class="font-mono font-semibold tabular-nums text-slate-900">{{ flowdesk_format_minor((int) $pp->price_minor, $plan->currency) }} {{ $plan->currency }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <a
                        href="{{ route('admin.plans.edit', $plan) }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                        title="{{ __('Edit plan') }}"
                        aria-label="{{ __('Edit plan') }}"
                    >
                        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                    </a>
                </div>

                <div class="mt-5 border-t border-slate-200/70 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Plan features') }}</p>
                    @php($featureRows = $planFeatureSummaries[$plan->id] ?? [])
                    @if (count($featureRows) === 0)
                        <p class="mt-2 text-sm text-slate-600">{{ __('No features configured yet.') }}</p>
                    @else
                        <div class="mt-3">
                            @include('partials.plan-feature-list', ['featureRows' => $featureRows, 'compact' => true])
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
