@php
    $rows = old('tiers', $tierRows);
    if (! is_array($rows)) {
        $rows = $tierRows;
    }
    while (count($rows) < 5) {
        $rows[] = ['from_clients' => '', 'to_clients' => '', 'percent' => ''];
    }
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Provider commission by client volume') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->has('tiers'))
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first('tiers') }}</div>
            @endif

            <div class="flow-panel p-6 sm:p-8">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('These bands apply to all business providers in this workspace: when the total client count falls in a band, that commission rate is used for eligible deals. Each provider still has a fixed percentage on their profile for when no band matches or when these rules are empty.') }}</p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Leave all rows empty to rely only on each provider’s fixed commission rate.') }}</p>

                <div class="mt-4 rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-3 text-sm dark:border-slate-600/50 dark:bg-slate-800/30">
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ __('Current workspace clients') }}:</span>
                    <span class="tabular-nums text-slate-900 dark:text-white">{{ number_format($clientCount) }}</span>
                    @if ($previewRate !== null)
                        <span class="ms-2 text-slate-600 dark:text-slate-400">— {{ __('Effective rate now') }}: <strong class="text-slate-900 dark:text-white">{{ number_format($previewRate * 100, 2) }}%</strong></span>
                    @else
                        <span class="ms-2 text-slate-500 dark:text-slate-400">— {{ __('No matching band; each provider’s fixed rate applies.') }}</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('settings.provider-commissions.update') }}" class="mt-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <div class="hidden gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:grid sm:grid-cols-[1fr_1fr_1fr]">
                            <span>{{ __('Min clients') }}</span>
                            <span>{{ __('Max clients') }}</span>
                            <span>{{ __('Commission %') }}</span>
                        </div>
                        @foreach (array_slice($rows, 0, 5) as $i => $row)
                            <div class="grid gap-2 sm:grid-cols-[1fr_1fr_1fr]">
                                <input type="number" name="tiers[{{ $i }}][from_clients]" min="0" class="flow-input" value="{{ $row['from_clients'] ?? '' }}" placeholder="0" />
                                <input type="number" name="tiers[{{ $i }}][to_clients]" min="0" class="flow-input" value="{{ $row['to_clients'] ?? '' }}" placeholder="{{ __('No max') }}" />
                                <input type="number" name="tiers[{{ $i }}][percent]" step="0.01" min="0" max="100" class="flow-input" value="{{ $row['percent'] ?? '' }}" placeholder="10" />
                            </div>
                        @endforeach
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Bands are inclusive. Leave max empty for “and above”. Example: 0–10 clients at 8%, 11+ at 5%.') }}</p>
                    </div>

                    <x-primary-button>{{ __('Save commission rules') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
