@php
    $suggested = [
        'title' => __('hr_payroll_default_title', ['month' => now()->translatedFormat('F Y')]),
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'pay_date' => now()->endOfMonth()->addDays(5)->toDateString(),
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ __('hr_payroll') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full space-y-6 sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('hr_payroll')" :description="__('hr_payroll_intro')" />

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flow-panel p-6 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_new_payroll_run') }}</h3>
                    <form method="POST" action="{{ route('hr.payroll.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="payroll_title" :value="__('Title')" />
                            <x-text-input id="payroll_title" name="title" class="mt-1 block w-full" required :value="old('title', $suggested['title'])" />
                        </div>
                        <div>
                            <x-input-label for="period_start" :value="__('hr_period_start')" />
                            <x-text-input id="period_start" name="period_start" type="date" class="mt-1 block w-full" required :value="old('period_start', $suggested['period_start'])" />
                        </div>
                        <div>
                            <x-input-label for="period_end" :value="__('hr_period_end')" />
                            <x-text-input id="period_end" name="period_end" type="date" class="mt-1 block w-full" required :value="old('period_end', $suggested['period_end'])" />
                        </div>
                        <div>
                            <x-input-label for="pay_date" :value="__('hr_pay_date')" />
                            <x-text-input id="pay_date" name="pay_date" type="date" class="mt-1 block w-full" required :value="old('pay_date', $suggested['pay_date'])" />
                        </div>
                        <x-primary-button type="submit">{{ __('Create') }}</x-primary-button>
                    </form>
                </div>

                <div class="flow-panel overflow-hidden p-0 lg:col-span-2">
                    <x-flow.table>
                        <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Title') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Period') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('hr_pay_date') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Payslips') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                            @forelse ($runs as $run)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $run->title }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $run->period_start->format('Y-m-d') }} → {{ $run->period_end->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $run->pay_date->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $run->status->badgeClass() }}">{{ $run->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm tabular-nums">{{ $run->payslips_count }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('hr.payroll.show', $run) }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_payroll_runs') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-flow.table>
                    <div class="p-4">{{ $runs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
