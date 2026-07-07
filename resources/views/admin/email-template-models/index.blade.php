<x-admin-layout :title="__('admin_email_template_models_title')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a
            href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Dashboard') }}</span>
        </a>
        <a
            href="{{ route('admin.email-template-models.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
        >
            <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
            <span>{{ __('admin_email_template_model_create') }}</span>
        </a>
    </div>

    <x-flow.page-header
        :title="__('admin_email_template_models_title')"
        :description="__('admin_email_template_models_intro')"
    />

    @if ($models->isEmpty())
        <p class="mt-6 text-sm text-slate-600">{{ __('admin_email_template_models_empty') }}</p>
    @else
        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-3 text-start">{{ __('admin_email_template_model_slug') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('Name') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('Category') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('admin_email_template_model_sort_order') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('admin_email_template_model_active') }}</th>
                        <th class="px-3 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($models as $row)
                        <tr>
                            <td class="px-3 py-3 font-mono text-xs text-slate-700 dark:text-slate-300 text-start">{{ $row->slug }}</td>
                            <td class="px-3 py-3 font-medium text-slate-900 dark:text-white text-start">{{ $row->name }}</td>
                            <td class="px-3 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $row->category ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $row->sort_order }}</td>
                            <td class="px-3 py-3 text-start">
                                @if ($row->is_active)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ __('admin_email_template_model_status_on') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('admin_email_template_model_status_off') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-end">
                                <div class="flex justify-end gap-2">
                                    <a
                                        href="{{ route('admin.email-template-models.edit', $row) }}"
                                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                    >{{ __('Edit') }}</a>
                                    <form
                                        method="POST"
                                        action="{{ route('admin.email-template-models.destroy', $row) }}"
                                        class="inline"
                                        onsubmit="return confirm(@json(__('admin_email_template_model_delete_confirm')))"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-800 hover:bg-rose-100"
                                        >{{ __('Remove') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin-layout>
