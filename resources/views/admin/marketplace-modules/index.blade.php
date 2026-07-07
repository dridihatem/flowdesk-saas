<x-admin-layout :title="__('admin_marketplace_modules_title')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
            <span>{{ __('Dashboard') }}</span>
        </a>
        <a href="{{ route('admin.marketplace-modules.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
            <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
            <span>{{ __('admin_marketplace_module_create') }}</span>
        </a>
    </div>

    <x-flow.page-header :title="__('admin_marketplace_modules_title')" :description="__('admin_marketplace_modules_intro')" />

    @if (session('status'))
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif

    @if ($modules->isEmpty())
        <p class="mt-6 text-sm text-slate-600">{{ __('admin_marketplace_modules_empty') }}</p>
    @else
        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-3 text-start">{{ __('Name') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('Category') }}</th>
                        <th class="px-3 py-3 text-end">{{ __('Price') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('admin_marketplace_module_billing_period') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('admin_marketplace_module_zip') }}</th>
                        <th class="px-3 py-3 text-start">{{ __('admin_marketplace_module_published') }}</th>
                        <th class="px-3 py-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($modules as $row)
                        <tr>
                            <td class="px-3 py-3 text-start">
                                <p class="font-medium text-slate-900">{{ $row->name }}</p>
                                <p class="font-mono text-xs text-slate-500">{{ $row->slug }}</p>
                            </td>
                            <td class="px-3 py-3 text-slate-600 text-start">{{ $row->category->label() }}</td>
                            <td class="px-3 py-3 text-end font-semibold"><span class="flowdesk-ltr-num tabular-nums font-semibold">{{ flowdesk_format_minor((int) $row->price_minor, $row->currency) }} {{ $row->currency }}</span></td>
                            <td class="px-3 py-3 text-slate-600 text-start">{{ $row->billing_period->label() }}</td>
                            <td class="px-3 py-3 text-start">
                                @if ($row->zip_path)
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('admin_marketplace_module_zip_yes') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">{{ __('admin_marketplace_module_zip_no') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-start">
                                @if ($row->is_published)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{{ __('admin_email_template_model_status_on') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ __('admin_email_template_model_status_off') }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-end">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.marketplace-modules.edit', $row) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.marketplace-modules.destroy', $row) }}" class="inline" onsubmit="return confirm(@json(__('admin_marketplace_module_delete_confirm')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-800 hover:bg-rose-100">{{ __('Remove') }}</button>
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
