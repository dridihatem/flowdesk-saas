<x-app-layout>
    <div class="py-10">
        <div class="max-w-6xl w-full sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
                <a href="{{ route('email-marketing.campaigns.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    ← {{ __('Campaigns') }}
                </a>
                <a href="{{ route('email-marketing.campaigns.edit', $campaign) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-500 dark:text-slate-400 dark:hover:text-slate-300">
                    {{ __('email_marketing_campaign_back_to_edit') }} →
                </a>
            </div>
            <x-flow.page-header
                :title="$campaign->name"
                :description="__('email_marketing_campaign_stats_intro')"
            />

            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_open_tracking_note') }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <x-flow.stat-card :label="__('email_marketing_stat_sent')" variant="indigo">
                    {{ number_format($stats['sent']) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('email_marketing_stat_opened')" variant="emerald">
                    {{ number_format($stats['opened']) }}
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('email_marketing_stat_open_rate')" variant="amber">
                    @if ($stats['rate'] !== null)
                        {{ $stats['rate'] }}%
                    @else
                        —
                    @endif
                </x-flow.stat-card>
            </div>

            @if ($deliveries->isEmpty())
                <div class="mt-8 rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-6 text-sm text-slate-600 dark:border-slate-600/50 dark:bg-slate-800/20 dark:text-slate-300">
                    {{ __('email_marketing_no_deliveries_yet') }}
                </div>
            @else
                <div class="mt-8 flow-panel overflow-hidden p-0">
                    <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                            <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3 text-start">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('email_marketing_col_sent') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('email_marketing_col_opened') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('email_marketing_col_opens') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($deliveries as $d)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-200 text-start">{{ $d->recipient_email }}</td>
                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $d->sent_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-start">
                                        @if ($d->first_opened_at)
                                            <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ __('email_marketing_yes') }}</span>
                                        @else
                                            <span class="text-slate-500 dark:text-slate-400">{{ __('email_marketing_no') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end text-slate-600 dark:text-slate-400">{{ number_format($d->open_count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $deliveries->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
