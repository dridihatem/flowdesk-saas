<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ $employee->full_name }}</h2>
            <a href="{{ route('hr.employees.edit', $employee) }}">
                <x-secondary-button type="button" class="!normal-case">{{ __('Edit') }}</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flow-panel space-y-4 p-6 lg:col-span-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $employee->status->badgeClass() }}">{{ $employee->status->label() }}</span>
                        <span class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $employee->employee_number }}</span>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Job title') }}</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ $employee->job_title ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('hr_department') }}</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ $employee->department?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('hr_employment_type') }}</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ $employee->employment_type->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('hr_base_salary') }}</dt>
                            <dd class="font-medium tabular-nums text-slate-900 dark:text-white">{{ $employee->formattedSalary() }} · {{ $employee->pay_frequency->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Email') }}</dt>
                            <dd>{{ $employee->email ?? $employee->user?->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('hr_hire_date') }}</dt>
                            <dd>{{ $employee->hire_date?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="flow-panel p-6">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_recent_leave') }}</h3>
                        <ul class="mt-4 divide-y divide-slate-200/80 dark:divide-slate-700/80">
                            @forelse ($employee->leaveRequests as $leave)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                                    <div>
                                        <p class="font-medium">{{ $leave->leaveType?->name }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $leave->starts_on->format('Y-m-d') }} → {{ $leave->ends_on->format('Y-m-d') }} · {{ $leave->days_count }} {{ __('days') }}</p>
                                    </div>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $leave->status->badgeClass() }}">{{ $leave->status->label() }}</span>
                                </li>
                            @empty
                                <li class="py-4 text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_leave_yet') }}</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="flow-panel p-6">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_recent_payslips') }}</h3>
                        <ul class="mt-4 divide-y divide-slate-200/80 dark:divide-slate-700/80">
                            @forelse ($employee->payslips as $payslip)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                                    <div>
                                        <p class="font-medium">{{ $payslip->payrollRun?->title ?? '—' }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $payslip->payrollRun?->pay_date?->format('Y-m-d') }}</p>
                                    </div>
                                    <span class="font-medium tabular-nums">{{ flowdesk_format_minor((int) $payslip->net_minor, $payslip->currency) }} {{ $payslip->currency }}</span>
                                </li>
                            @empty
                                <li class="py-4 text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_payslips_yet') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
