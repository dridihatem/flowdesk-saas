@php
    $isEdit = isset($employee);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
            {{ $isEdit ? __('hr_edit_employee') : __('hr_add_employee') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8">
            <form method="POST" action="{{ $isEdit ? route('hr.employees.update', $employee) : route('hr.employees.store') }}" class="flow-panel space-y-6 p-6">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="full_name" :value="__('Full name')" />
                        <x-text-input id="full_name" name="full_name" class="mt-1 block w-full" required :value="old('full_name', $employee->full_name ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="employee_number" :value="__('hr_employee_number')" />
                        <x-text-input id="employee_number" name="employee_number" class="mt-1 block w-full" :value="old('employee_number', $employee->employee_number ?? $suggestedEmployeeNumber)" />
                    </div>
                    <div>
                        <x-input-label for="user_id" :value="__('hr_link_workspace_user')" />
                        <select id="user_id" name="user_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach ($staffUsers as $user)
                                <option value="{{ $user->id }}" @selected((string) old('user_id', $employee->user_id ?? '') === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $employee->email ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="phone" :value="__('Phone')" />
                        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $employee->phone ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="department_id" :value="__('hr_department')" />
                        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id', $employee->department_id ?? '') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="job_title" :value="__('Job title')" />
                        <x-text-input id="job_title" name="job_title" class="mt-1 block w-full" :value="old('job_title', $employee->job_title ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="employment_type" :value="__('hr_employment_type')" />
                        <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type->value }}" @selected(old('employment_type', $employee->employment_type->value ?? 'full_time') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            @foreach ($statuses as $st)
                                <option value="{{ $st->value }}" @selected(old('status', $employee->status->value ?? 'active') === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="hire_date" :value="__('hr_hire_date')" />
                        <x-text-input id="hire_date" name="hire_date" type="date" class="mt-1 block w-full" :value="old('hire_date', optional($employee->hire_date ?? null)?->toDateString())" />
                    </div>
                    <div>
                        <x-input-label for="termination_date" :value="__('hr_termination_date')" />
                        <x-text-input id="termination_date" name="termination_date" type="date" class="mt-1 block w-full" :value="old('termination_date', optional($employee->termination_date ?? null)?->toDateString())" />
                    </div>
                    <div>
                        <x-input-label for="salary_amount" :value="__('hr_base_salary')" />
                        <x-text-input id="salary_amount" name="salary_amount" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('salary_amount', isset($employee) && $employee->base_salary_minor ? flowdesk_major_amount_for_input((int) $employee->base_salary_minor, $employee->salaryCurrency($defaultCurrency)) : '')" />
                    </div>
                    <div>
                        <x-input-label for="salary_currency" :value="__('Currency')" />
                        <x-text-input id="salary_currency" name="salary_currency" class="mt-1 block w-full uppercase" maxlength="3" :value="old('salary_currency', $employee->salary_currency ?? $defaultCurrency)" />
                    </div>
                    <div>
                        <x-input-label for="pay_frequency" :value="__('hr_pay_frequency')" />
                        <select id="pay_frequency" name="pay_frequency" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            @foreach ($payFrequencies as $freq)
                                <option value="{{ $freq->value }}" @selected(old('pay_frequency', $employee->pay_frequency->value ?? 'monthly') === $freq->value)>{{ $freq->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="bank_iban" :value="__('hr_bank_iban')" />
                        <x-text-input id="bank_iban" name="bank_iban" class="mt-1 block w-full flowdesk-ltr-num" :value="old('bank_iban', $employee->bank_iban ?? '')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="notes" :value="__('Notes')" />
                        <textarea id="notes" name="notes" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('notes', $employee->notes ?? '') }}</textarea>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                    <a href="{{ $isEdit ? route('hr.employees.show', $employee) : route('hr.employees.index') }}">
                        <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
