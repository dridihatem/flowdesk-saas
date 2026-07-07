<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">{{ __('hr_departments') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('hr_departments')" :description="__('hr_departments_intro')" />

            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            <x-input-error class="mb-4" :messages="$errors->get('department')" />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flow-panel p-6 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('hr_add_department') }}</h3>
                    <form method="POST" action="{{ route('hr.departments.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="dept_name" :value="__('Name')" />
                            <x-text-input id="dept_name" name="name" class="mt-1 block w-full" required :value="old('name')" />
                        </div>
                        <div>
                            <x-input-label for="dept_description" :value="__('Description')" />
                            <textarea id="dept_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('description') }}</textarea>
                        </div>
                        <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                    </form>
                </div>

                <div class="flow-panel overflow-hidden p-0 lg:col-span-2">
                    <x-flow.table>
                        <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Employees') }}</th>
                                <th class="px-4 py-3 text-start">{{ __('Manager') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                            @forelse ($departments as $department)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ $department->name }}</td>
                                    <td class="px-4 py-3 text-sm tabular-nums">{{ $department->employees_count }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $department->manager?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-end">
                                        <form method="POST" action="{{ route('hr.departments.destroy', $department) }}" class="inline" onsubmit="return confirm(@json(__('hr_department_delete_confirm')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-rose-600 hover:underline dark:text-rose-400">{{ __('Delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('hr_no_departments') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </x-flow.table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
