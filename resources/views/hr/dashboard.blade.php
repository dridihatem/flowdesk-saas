<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ __('hr_dashboard_title') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full space-y-8 sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('hr_dashboard_title')" :description="__('hr_dashboard_intro')" />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-flow.stat-card :label="__('hr_stat_employees')" variant="indigo">
                    <span class="text-3xl font-bold tabular-nums">{{ $activeEmployees }}</span>
                    <span class="text-sm text-slate-500 dark:text-slate-400">/ {{ $employeesCount }}</span>
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('hr_stat_departments')" variant="cyan">
                    <span class="text-3xl font-bold tabular-nums">{{ $departmentsCount }}</span>
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('hr_stat_pending_leave')" variant="amber">
                    <span class="text-3xl font-bold tabular-nums">{{ $pendingLeave }}</span>
                </x-flow.stat-card>
                <x-flow.stat-card :label="__('hr_stat_latest_payroll')" variant="emerald">
                    <span class="text-lg font-semibold">{{ $latestPayroll?->title ?? '—' }}</span>
                </x-flow.stat-card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="flow-panel p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_recent_employees') }}</h3>
                        <a href="{{ route('hr.employees.create') }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('hr_add_employee') }}</a>
                    </div>
                    <ul class="mt-4 divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($recentEmployees as $employee)
                            <li class="flex items-center justify-between gap-3 py-3 text-sm">
                                <div>
                                    <a href="{{ route('hr.employees.show', $employee) }}" class="font-medium text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">{{ $employee->full_name }}</a>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $employee->job_title ?? '—' }} · {{ $employee->department?->name ?? __('hr_no_department') }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $employee->status->badgeClass() }}">{{ $employee->status->label() }}</span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_employees_yet') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="flow-panel p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_quick_actions') }}</h3>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('hr.employees.index') }}" class="rounded-xl border border-slate-200/80 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-slate-700 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30">
                            <p class="font-medium text-slate-900 dark:text-white">{{ __('Employees') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('hr_manage_employees_hint') }}</p>
                        </a>
                        <a href="{{ route('hr.departments.index') }}" class="rounded-xl border border-slate-200/80 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-slate-700 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30">
                            <p class="font-medium text-slate-900 dark:text-white">{{ __('hr_departments') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('hr_manage_departments_hint') }}</p>
                        </a>
                        <a href="{{ route('hr.leave.index') }}" class="rounded-xl border border-slate-200/80 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-slate-700 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30">
                            <p class="font-medium text-slate-900 dark:text-white">{{ __('hr_leave') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('hr_manage_leave_hint') }}</p>
                        </a>
                        <a href="{{ route('hr.payroll.index') }}" class="rounded-xl border border-slate-200/80 p-4 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-slate-700 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30">
                            <p class="font-medium text-slate-900 dark:text-white">{{ __('hr_payroll') }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('hr_manage_payroll_hint') }}</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
