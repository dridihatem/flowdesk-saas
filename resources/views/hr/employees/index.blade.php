<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ __('Employees') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('Employees')">
                <x-slot name="actions">
                    <form method="POST" action="{{ route('hr.employees.sync-team') }}">
                        @csrf
                        <x-secondary-button type="submit" class="!normal-case">{{ __('hr_sync_team_members') }}</x-secondary-button>
                    </form>
                    <a href="{{ route('hr.employees.create') }}">
                        <x-primary-button type="button" class="!normal-case">{{ __('hr_add_employee') }}</x-primary-button>
                    </a>
                </x-slot>
            </x-flow.page-header>

            <form method="GET" class="mb-6 flex flex-wrap gap-3">
                <x-text-input name="q" :value="$q" class="max-w-md" placeholder="{{ __('hr_search_employees') }}" />
                <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (\App\Enums\HrEmployeeStatus::cases() as $st)
                        <option value="{{ $st->value }}" @selected($status === $st->value)>{{ $st->label() }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
            </form>

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($importedCount > 0)
                <div class="mb-4 rounded-xl border border-indigo-200/80 bg-indigo-50/90 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/50 dark:text-indigo-100">
                    {{ __('hr_team_auto_imported', ['count' => $importedCount]) }}
                </div>
            @endif

            <div class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('hr_employee_number') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('hr_workspace_account') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('hr_department') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Job title') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('hr_base_salary') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="px-4 py-3 font-mono text-sm">{{ $employee->employee_number ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $employee->full_name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $employee->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $employee->department?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $employee->job_title ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums">{{ $employee->formattedSalary() }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $employee->status->badgeClass() }}">{{ $employee->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('hr.employees.show', $employee) }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_employees_yet') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            <div class="mt-4">{{ $employees->links() }}</div>
        </div>
    </div>
</x-app-layout>
