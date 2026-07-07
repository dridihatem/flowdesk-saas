<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ $run->title }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel flex flex-wrap items-center justify-between gap-4 p-6">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $run->period_start->format('Y-m-d') }} → {{ $run->period_end->format('Y-m-d') }} · {{ __('hr_pay_date') }}: {{ $run->pay_date->format('Y-m-d') }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $run->status->badgeClass() }}">{{ $run->status->label() }}</span>
                        <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                            {{ __('Total net') }}: {{ flowdesk_format_minor($run->totalNetMinor(), $currency) }} {{ $currency }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($run->status === \App\Enums\HrPayrollRunStatus::Draft)
                        <form method="POST" action="{{ route('hr.payroll.generate', $run) }}">
                            @csrf
                            <x-secondary-button type="submit" class="!normal-case">{{ __('hr_generate_payslips') }}</x-secondary-button>
                        </form>
                        @if ($run->payslips->isNotEmpty())
                            <form method="POST" action="{{ route('hr.payroll.finalize', $run) }}">
                                @csrf
                                <x-primary-button type="submit" class="!normal-case">{{ __('hr_finalize_payroll') }}</x-primary-button>
                            </form>
                        @endif
                    @elseif ($run->status === \App\Enums\HrPayrollRunStatus::Finalized)
                        <form method="POST" action="{{ route('hr.payroll.mark-paid', $run) }}">
                            @csrf
                            <x-primary-button type="submit" class="!normal-case">{{ __('hr_mark_as_paid') }}</x-primary-button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Employee') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Job title') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Gross') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Deductions') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Net') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($run->payslips as $payslip)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium">{{ $payslip->employee?->full_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $payslip->employee?->job_title ?? '—' }}</td>
                                <td class="px-4 py-3 text-end text-sm tabular-nums">{{ flowdesk_format_minor((int) $payslip->gross_minor, $payslip->currency) }}</td>
                                <td class="px-4 py-3 text-end text-sm tabular-nums">{{ flowdesk_format_minor((int) $payslip->deductions_minor, $payslip->currency) }}</td>
                                <td class="px-4 py-3 text-end text-sm font-medium tabular-nums">{{ flowdesk_format_minor((int) $payslip->net_minor, $payslip->currency) }} {{ $payslip->currency }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_payslips_generate') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            <a href="{{ route('hr.payroll.index') }}" class="inline-flex text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Back to payroll') }}</a>
        </div>
    </div>
</x-app-layout>
