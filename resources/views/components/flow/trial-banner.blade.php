@php
    $trial = $flowdeskTrialBanner ?? ['show' => false];
@endphp
@if (($trial['show'] ?? false) && auth()->user()?->hasRole('company_admin'))
    <div class="max-w-12xl w-full px-4 pt-6 sm:px-6 lg:px-8">
        <div @class([
            'rounded-xl border px-4 py-3 text-sm shadow-sm',
            'border-amber-200/90 bg-amber-50/95 text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/40 dark:text-amber-100' => ! ($trial['expired'] ?? false),
            'border-rose-200/90 bg-rose-50/95 text-rose-950 dark:border-rose-500/30 dark:bg-rose-950/40 dark:text-rose-100' => ($trial['expired'] ?? false),
        ])>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @if ($trial['expired'] ?? false)
                        <p class="font-semibold">{{ __('trial_expired_title') }}</p>
                        <p class="mt-1 text-xs opacity-90">{{ __('trial_expired_body') }}</p>
                    @else
                        <p class="font-semibold">{{ __('trial_active_title', ['plan' => $trial['plan_name'] ?? __('Pro')]) }}</p>
                        <p class="mt-1 text-xs opacity-90">
                            {{ __('trial_active_body', [
                                'days' => (int) ($trial['days_left'] ?? 0),
                                'date' => $trial['ends_at'] ?? '',
                            ]) }}
                        </p>
                    @endif
                </div>
                <a
                    href="{{ route('billing.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                >
                    {{ __('trial_view_plans') }}
                </a>
            </div>
        </div>
    </div>
@endif
