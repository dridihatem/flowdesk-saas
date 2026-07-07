<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('Audiences')"
                :description="__('email_marketing_audiences_intro')"
            />

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mt-8 flex justify-end">
                <a href="{{ route('email-marketing.audiences.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                    <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                    <span>{{ __('email_marketing_audience_create') }}</span>
                </a>
            </div>

            <div class="mt-6">
                @if ($audiences->isEmpty())
                    @include('email-marketing.partials.empty', ['message' => __('email_marketing_audiences_empty')])
                @else
                    <div class="flow-panel overflow-hidden p-0">
                        <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                                <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Description') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Recipients') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Updated') }}</th>
                                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($audiences as $a)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white text-start">{{ $a->name }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ \Illuminate\Support\Str::limit($a->description ?? '—', 80) }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $a->contacts_count }}</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-start">{{ $a->updated_at?->diffForHumans() }}</td>
                                        <td class="px-4 py-3 text-end">
                                            <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                                <a
                                                    href="{{ route('email-marketing.audiences.edit', $a) }}"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                    title="{{ __('Edit') }}"
                                                >
                                                    <i class="fa-solid fa-pen-to-square text-sm" aria-hidden="true"></i>
                                                    <span class="sr-only">{{ __('Edit') }}</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $audiences->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
