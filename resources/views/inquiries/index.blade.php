<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
            {{ __('Inquiries') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-end">
                <a href="{{ route('inquiries.create') }}">
                    <x-primary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                        <i class="fa-solid fa-circle-plus text-sm" aria-hidden="true"></i>
                        {{ __('New inquiry') }}
                    </x-primary-button>
                </a>
            </div>

            <div class="flow-panel mb-8 p-6 sm:p-8">
                <form method="GET" action="{{ route('inquiries.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="inq_status" :value="__('Status')" />
                        <select id="inq_status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 bg-white shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 sm:min-w-[12rem]">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Enums\InquiryStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ \Illuminate\Support\Str::headline($case->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-secondary-button type="submit" class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
                        {{ __('Filter') }}
                    </x-secondary-button>
                </form>
            </div>

            <div class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Subject') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Contact') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Created') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                        @forelse ($inquiries as $inquiry)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-medium text-start">
                                    <a href="{{ route('inquiries.show', $inquiry) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $inquiry->subject }}</a>
                                </td>
                                <td class="px-4 py-3 text-start">
                                    <x-flow.badge variant="primary">{{ \Illuminate\Support\Str::headline($inquiry->status->name) }}</x-flow.badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">
                                    {{ $inquiry->contact_name ?? '—' }}
                                    @if ($inquiry->contact_email)
                                        <span class="block text-xs text-slate-500">{{ $inquiry->contact_email }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 text-start">{{ $inquiry->created_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <a
                                        href="{{ route('inquiries.show', $inquiry) }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                        title="{{ __('View') }}"
                                    >
                                        <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No inquiries yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            <div class="mt-6">
                {{ $inquiries->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
