<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Submissions') }} — {{ $form->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('forms.edit', $form) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">← {{ __('Back to form editor') }}</a>

            <div class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Data') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">{{ $submission->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-start">
                                    <pre class="max-h-32 overflow-auto rounded bg-slate-50 p-2 text-xs dark:bg-slate-900/50">{{ json_encode($submission->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <form method="POST" action="{{ route('form-submissions.convert-project', $submission) }}" class="inline">
                                        @csrf
                                        <x-secondary-button type="submit" class="!py-1 !text-xs">{{ __('Create project') }}</x-secondary-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-12 text-center text-sm text-slate-500">{{ __('No submissions yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>
            <div class="mt-6">{{ $submissions->links() }}</div>
        </div>
    </div>
</x-app-layout>
