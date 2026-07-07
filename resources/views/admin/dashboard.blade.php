<x-admin-layout>
    <x-flow.page-header
        :title="__('Platform administration')"
        :description="__('Manage customer companies, subscription plans, and invoice payments recorded across all workspaces. Company accounts run projects, quotes (proposals), and invoices on their own tenant.')"
    />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-flow.stat-card :label="__('Companies')" variant="indigo">{{ number_format($companiesCount) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Subscription plans')" variant="cyan">{{ number_format($plansCount) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Active subscriptions')" variant="emerald">{{ number_format($activeSubscriptions) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Invoice payments (rows)')" variant="amber">{{ number_format($paymentsCount) }}</x-flow.stat-card>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="flow-panel lg:col-span-2 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Revenue (completed payments)') }}</h3>
                    <p class="mt-1 text-xs text-slate-600">{{ __('Last 12 months') }}</p>
                </div>
            </div>
            <div
                id="flowdesk-dashboard-charts-root"
                class="mt-4 grid gap-4 lg:grid-cols-2"
                data-chart='@json($reportsChart)'
                data-label-invoices="{{ __('Payments') }}"
                data-label-paid="{{ __('Revenue (major units)') }}"
                data-label-companies="{{ __('Companies created') }}"
            >
                <div class="h-[260px] rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3 dark:border-slate-700/80 dark:bg-slate-900/40">
                    <canvas id="chart-dashboard-invoices"></canvas>
                </div>
                <div class="h-[260px] rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3 dark:border-slate-700/80 dark:bg-slate-900/40">
                    <canvas id="chart-dashboard-revenue"></canvas>
                </div>
            </div>
        </div>

        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('New companies') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Last 12 months') }}</p>
            <div class="mt-4 h-[280px]">
                <canvas id="chart-admin-companies"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Revenue by plan') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Completed payments grouped by current company plan.') }}</p>

            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/70 bg-white">
                <table class="min-w-full table-fixed text-start divide-y divide-slate-200/70 text-sm">
                    <thead class="bg-slate-50 text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Plan') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Active subscriptions') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Payments') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Revenue (major units)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 text-slate-800">
                        @forelse ($revenueByPlan as $row)
                            @php($subs = $subsByPlan[$row->plan_id] ?? null)
                            <tr>
                                <td class="px-4 py-3 text-start">
                                    <div class="font-semibold text-slate-900">{{ $row->plan_name ?? __('No plan') }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $row->plan_slug ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-end">{{ number_format((int) ($subs->subs_count ?? 0)) }}</td>
                                <td class="px-4 py-3 text-end">{{ number_format((int) ($row->payments_count ?? 0)) }}</td>
                                <td class="px-4 py-3 text-end font-semibold">
                                    {{ number_format((int) round(((int) ($row->total_minor ?? 0)) / 100), 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">{{ __('No data yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flow-panel p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Companies by plan') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Current companies grouped by assigned plan.') }}</p>

            <div class="mt-4 space-y-2">
                @foreach ($plans as $p)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-900">{{ $p->name }}</div>
                            <div class="font-mono text-xs text-slate-500">{{ $p->slug }}</div>
                        </div>
                        <div class="font-semibold text-slate-900">{{ number_format((int) ($companiesByPlan[$p->id] ?? 0)) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-10 flow-panel p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Quick actions') }}</h3>
                <p class="mt-1 text-xs text-slate-600">{{ __('Common admin tasks') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    <i class="fa-solid fa-building-circle-plus text-sm" aria-hidden="true"></i>
                    <span>{{ __('Create company') }}</span>
                </a>
                <a href="{{ route('admin.plans.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                    <i class="fa-regular fa-credit-card text-xs" aria-hidden="true"></i>
                    <span>{{ __('Subscription plans') }}</span>
                </a>
                <a href="{{ route('admin.payment-gateways.edit') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                    <i class="fa-solid fa-plug-circle-bolt text-xs" aria-hidden="true"></i>
                    <span>{{ __('Payment gateways') }}</span>
                </a>
                <a href="{{ route('admin.themes.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                    <i class="fa-solid fa-palette text-xs" aria-hidden="true"></i>
                    <span>{{ __('Theme library') }}</span>
                </a>
                <a href="{{ route('admin.email-template-models.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                    <i class="fa-regular fa-envelope-open text-xs" aria-hidden="true"></i>
                    <span>{{ __('admin_email_template_models_nav') }}</span>
                </a>
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('admin.platform-appearance.edit') }}" class="flow-panel block p-6 transition hover:border-indigo-300 dark:hover:border-indigo-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Workspace theme') }}</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Default layout, colors, fonts, and CSS for all companies.') }}</p>
        </a>
        <a href="{{ route('admin.plans.index') }}" class="flow-panel block p-6 transition hover:border-indigo-300 dark:hover:border-indigo-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Plans & features') }}</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Pricing and per-plan feature limits (projects, users, AI credits, …).') }}</p>
        </a>
        <a href="{{ route('admin.companies.index') }}" class="flow-panel block p-6 transition hover:border-indigo-300 dark:hover:border-indigo-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Companies') }}</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('View workspaces and subscription status.') }}</p>
        </a>
        <a href="{{ route('admin.email-template-models.index') }}" class="flow-panel block p-6 transition hover:border-indigo-300 dark:hover:border-indigo-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('admin_email_template_models_title') }}</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('admin_email_template_models_card_blurb') }}</p>
        </a>
    </div>

    <p class="mt-8 text-sm text-slate-600 dark:text-slate-400">
        {{ __('Use the navigation above. Your company workspace (projects, quotes, invoices) is separate — sign in with a company user on your tenant URL.') }}
    </p>
</x-admin-layout>

@push('scripts')
    @vite(['resources/js/dashboard-charts.js'])
@endpush
