<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Forms') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header :title="__('Lead capture forms')">
                <x-slot name="actions">
                    <a href="{{ route('forms.create') }}">
                        <x-primary-button type="button">{{ __('Create form') }}</x-primary-button>
                    </a>
                </x-slot>
            </x-flow.page-header>

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Fields') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Submissions') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($forms as $form)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100 text-start">{{ $form->name }}</td>
                                <td class="px-4 py-3 text-start">
                                    <x-flow.badge :variant="$form->status === 'published' ? 'success' : 'neutral'">{{ ucfirst($form->status) }}</x-flow.badge>
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-200 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $form->fields_count }}</span></td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-200 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $form->submissions_count }}</span></td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                        <a
                                            href="{{ route('forms.submissions.index', $form) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('Submissions') }}"
                                        >
                                            <i class="fa-solid fa-inbox text-sm" aria-hidden="true"></i>
                                            <span class="sr-only">{{ __('Submissions') }}</span>
                                        </a>
                                        <a
                                            href="{{ route('forms.edit', $form) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('Edit') }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-sm" aria-hidden="true"></i>
                                            <span class="sr-only">{{ __('Edit') }}</span>
                                        </a>
                                        <form action="{{ route('forms.destroy', $form) }}" method="POST" class="inline" onsubmit="return confirm({{ json_encode(__('Delete this form and its fields?')) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-rose-200 hover:text-rose-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:text-rose-400"
                                                title="{{ __('Delete') }}"
                                            >
                                                <i class="fa-regular fa-trash-can text-sm" aria-hidden="true"></i>
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No forms yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            <div class="mt-6">{{ $forms->links() }}</div>
        </div>
    </div>
</x-app-layout>
