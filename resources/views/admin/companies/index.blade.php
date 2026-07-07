<x-admin-layout>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <x-flow.page-header
            :title="__('Companies')"
            :description="__('Each company is an isolated workspace: team members manage clients, projects, proposals (quotes), and invoices.')"
        />

        <a
            href="{{ route('admin.companies.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
        >
            <i class="fa-solid fa-building-circle-plus text-sm" aria-hidden="true"></i>
            <span>{{ __('Create company') }}</span>
        </a>
    </div>

    <div class="flow-panel overflow-hidden p-0">
        <x-flow.table>
            <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Subdomain') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Plan') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Users') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Created') }}</th>
                    <th class="px-4 py-3 text-start"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                @foreach ($companies as $company)
                    <tr>
                        <td class="px-4 py-3 font-medium text-start">{{ $company->name }}</td>
                        <td class="px-4 py-3 font-mono text-sm text-start">{{ $company->subdomain }}</td>
                        <td class="px-4 py-3 text-sm text-start">{{ $company->plan?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-start">{{ number_format($company->users_count) }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500 text-start">{{ $company->created_at?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-end">
                            <a
                                href="{{ route('admin.companies.show', $company) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-emerald-600 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-emerald-400"
                                title="{{ __('View company') }}"
                            >
                                <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                <span class="sr-only">{{ __('View company') }}</span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-flow.table>
    </div>
    <div class="mt-6">{{ $companies->links() }}</div>
</x-admin-layout>
