<x-admin-layout>
    <x-flow.page-header
        :title="__('Platform administration')"
        :description="__('Manage customer companies, subscription plans, and invoice payments recorded across all workspaces. Company accounts run projects, quotes (proposals), and invoices on their own tenant.')"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.companies.create') }}" class="flow-cta-link">
                <i class="fa-solid fa-building-circle-plus text-sm" aria-hidden="true"></i>
                <span>{{ __('Create company') }}</span>
            </a>
            <a href="{{ route('admin.plans.index') }}" class="flow-cta-secondary">
                <i class="fa-regular fa-credit-card text-xs" aria-hidden="true"></i>
                <span>{{ __('Subscription plans') }}</span>
            </a>
        </x-slot>
    </x-flow.page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-flow.stat-card :label="__('Companies')" variant="indigo">{{ number_format($companiesCount) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Subscription plans')" variant="cyan">{{ number_format($plansCount) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Active subscriptions')" variant="emerald">{{ number_format($activeSubscriptions) }}</x-flow.stat-card>
        <x-flow.stat-card :label="__('Invoice payments (rows)')" variant="amber">{{ number_format($paymentsCount) }}</x-flow.stat-card>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="flow-panel p-4 sm:p-6 xl:col-span-2">
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
                <div class="h-[220px] rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3 sm:h-[260px] dark:border-slate-700/80 dark:bg-slate-900/40">
                    <canvas id="chart-dashboard-invoices"></canvas>
                </div>
                <div class="h-[220px] rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3 sm:h-[260px] dark:border-slate-700/80 dark:bg-slate-900/40">
                    <canvas id="chart-dashboard-revenue"></canvas>
                </div>
            </div>
        </div>

        <div class="flow-panel p-4 sm:p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('New companies') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Last 12 months') }}</p>
            <div class="mt-4 h-[220px] sm:h-[280px]">
                <canvas id="chart-admin-companies"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="flow-panel p-4 sm:p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Revenue by plan') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Completed payments grouped by current company plan.') }}</p>

            <div class="flow-table-wrap mt-4">
                <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white">
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
        </div>

        <div class="flow-panel p-4 sm:p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Companies by plan') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Current companies grouped by assigned plan.') }}</p>

            <div class="mt-4 space-y-2">
                @foreach ($plans as $p)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-900">{{ $p->name }}</div>
                            <div class="font-mono text-xs text-slate-500">{{ $p->slug }} · {{ number_format((float) $p->price_monthly, 0) }} {{ $p->currency }}/{{ __('month') }}</div>
                        </div>
                        <div class="font-semibold text-slate-900">{{ number_format((int) ($companiesByPlan[$p->id] ?? 0)) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-10">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Quick actions') }}</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('admin.payment-gateways.edit') }}" class="flow-portal-card">
                <p class="font-semibold text-slate-900">{{ __('Payment gateways') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('Stripe, PayPal, and local providers.') }}</p>
            </a>
            <a href="{{ route('admin.themes.index') }}" class="flow-portal-card">
                <p class="font-semibold text-slate-900">{{ __('Theme library') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('Presets companies can apply in settings.') }}</p>
            </a>
            <a href="{{ route('admin.email-template-models.index') }}" class="flow-portal-card">
                <p class="font-semibold text-slate-900">{{ __('admin_email_template_models_nav') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('admin_email_template_models_card_blurb') }}</p>
            </a>
            <a href="{{ route('admin.platform-appearance.edit') }}" class="flow-portal-card">
                <p class="font-semibold text-slate-900">{{ __('Workspace theme') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('Default layout, colors, fonts, and CSS for all companies.') }}</p>
            </a>
            <a href="{{ route('admin.plans.index') }}" class="flow-portal-card">
                <p class="font-semibold text-slate-900">{{ __('Plans & features') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('Pricing and per-plan feature limits (projects, users, AI credits, …).') }}</p>
            </a>
            <a href="{{ route('admin.companies.index') }}" class="flow-portal-card">
                <p class="font-semibold text-slate-900">{{ __('Companies') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('View workspaces and subscription status.') }}</p>
            </a>
        </div>
    </div>

    <p class="mt-8 text-sm text-slate-600 dark:text-slate-400">
        {{ __('Use the navigation above. Your company workspace (projects, quotes, invoices) is separate — sign in with a company user on your tenant URL.') }}
    </p>
</x-admin-layout>

@push('scripts')
    @vite(['resources/js/dashboard-charts.js'])
@endpush
