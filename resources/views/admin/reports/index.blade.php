<x-admin-layout>
    <x-flow.page-header
        :title="__('Platform reports')"
        :description="__('Cross-tenant activity, revenue in the selected period, and per-company usage. Dates filter the period row and the company AI credits column.')"
    />

    <form method="GET" action="{{ route('admin.reports.index') }}" class="mb-8 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
        <div>
            <x-input-label for="rep_from" :value="__('From')" />
            <x-text-input id="rep_from" name="from" type="date" class="mt-1 block" :value="$from->format('Y-m-d')" />
        </div>
        <div>
            <x-input-label for="rep_to" :value="__('To')" />
            <x-text-input id="rep_to" name="to" type="date" class="mt-1 block" :value="$to->format('Y-m-d')" />
        </div>
        <x-primary-button type="submit">{{ __('Apply range') }}</x-primary-button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <x-flow.stat-card :label="__('Companies (total)')" variant="indigo">{{ number_format($snapshot['companies_total']) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Workspace users (total)')" variant="cyan">{{ number_format($snapshot['workspace_users_total']) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Projects (total)')" variant="emerald">{{ number_format($snapshot['projects_total']) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Invoices (total)')" variant="amber">{{ number_format($snapshot['invoices_total']) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('AI credits (all time)')" variant="indigo">{{ number_format($snapshot['ai_credits_all_time']) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Completed payments (total)')" variant="cyan">{{ number_format($snapshot['payments_completed_count']) }}</x-flow.stat-card>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flow-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('In selected period') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($period['new_companies']) }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('New companies') }}</p>
        </div>
        <div class="flow-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('In selected period') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($period['payments_completed_count']) }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Completed payments') }}</p>
        </div>
        <div class="flow-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('In selected period') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format((int) round($period['payments_completed_minor'] / 100)) }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Revenue (major units, all currencies summed)') }}</p>
        </div>
        <div class="flow-panel p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('In selected period') }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($period['ai_credits']) }}</p>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('AI credits recorded') }}</p>
        </div>
    </div>

    <div class="mt-8 flow-panel overflow-hidden p-0">
        <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Companies') }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Staff count, record volumes, and AI credits used in the selected period.') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 text-sm dark:divide-slate-700/80">
                <thead class="bg-slate-50 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 text-start">{{ __('Company') }}</th>
                        <th class="px-4 py-3 text-start">{{ __('Plan') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Staff') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Clients') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Projects') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('Invoices') }}</th>
                        <th class="px-4 py-3 text-end">{{ __('AI credits (period)') }}</th>
                        <th class="px-4 py-3 w-14 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/70">
                    @forelse ($companies as $row)
                        <tr class="text-slate-800 dark:text-slate-200">
                            <td class="px-4 py-3 text-start">
                                <a href="{{ route('admin.companies.show', $row) }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ $row->name }}</a>
                                <div class="font-mono text-xs text-slate-500">{{ $row->subdomain }}</div>
                            </td>
                            <td class="px-4 py-3 text-start">{{ $row->plan?->name ?? __('No plan') }}</td>
                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row->workspace_staff_count) }}</span></td>
                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row->clients_count) }}</span></td>
                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row->projects_count) }}</span></td>
                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ number_format($row->invoices_count) }}</span></td>
                            <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ number_format((int) ($row->ai_credits_period ?? 0)) }}</span></td>
                            <td class="px-4 py-3 text-end">
                                <a
                                    href="{{ route('admin.companies.show', $row) }}"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-emerald-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-emerald-400"
                                    title="{{ __('View company') }}"
                                >
                                    <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                    <span class="sr-only">{{ __('View company') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">{{ __('No companies yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($companies->hasPages())
            <div class="border-t border-slate-200/80 px-4 py-3 dark:border-slate-700/80">
                {{ $companies->links() }}
            </div>
        @endif
    </div>

    <p class="mt-6 text-xs text-slate-500 dark:text-slate-400">
        {{ __('Total revenue in the snapshot uses completed invoice payments (minor units). Summing mixed currencies is indicative only.') }}
    </p>
</x-admin-layout>
