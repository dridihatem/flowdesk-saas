<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ __('hr_leave') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full space-y-6 sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('hr_leave')" :description="__('hr_leave_intro')" />

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flow-panel p-6 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_request_leave') }}</h3>
                    <form method="POST" action="{{ route('hr.leave.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="leave_employee_id" :value="__('Employee')" />
                            <select id="leave_employee_id" name="employee_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                                <option value="">{{ __('Select an option') }}</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('employee_id') === $employee->id)>{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="leave_type_id" :value="__('hr_leave_type')" />
                            <select id="leave_type_id" name="leave_type_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                                @foreach ($leaveTypes as $type)
                                    <option value="{{ $type->id }}" @selected(old('leave_type_id') === $type->id)>{{ $type->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="starts_on" :value="__('Start date')" />
                            <x-text-input id="starts_on" name="starts_on" type="date" class="mt-1 block w-full" required :value="old('starts_on')" />
                        </div>
                        <div>
                            <x-input-label for="ends_on" :value="__('End date')" />
                            <x-text-input id="ends_on" name="ends_on" type="date" class="mt-1 block w-full" required :value="old('ends_on')" />
                        </div>
                        <div>
                            <x-input-label for="reason" :value="__('Reason')" />
                            <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('reason') }}</textarea>
                        </div>
                        <x-primary-button type="submit">{{ __('Submit') }}</x-primary-button>
                    </form>
                </div>

                <div class="flow-panel overflow-hidden p-0 lg:col-span-2">
                    <x-flow.table>
                        <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Employee') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('hr_leave_type') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Dates') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                            @forelse ($requests as $request)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $request->employee?->full_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $request->leaveType?->localizedName() }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $request->starts_on->format('Y-m-d') }} → {{ $request->ends_on->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $request->status->badgeClass() }}">{{ $request->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        @if ($request->status === \App\Enums\HrLeaveRequestStatus::Pending)
                                            <form method="POST" action="{{ route('hr.leave.approve', $request) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">{{ __('Approve') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('hr.leave.reject', $request) }}" class="ms-2 inline">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-rose-600 hover:underline dark:text-rose-400">{{ __('Reject') }}</button>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_leave_requests') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-flow.table>
                    <div class="p-4">{{ $requests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
