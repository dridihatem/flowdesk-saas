<x-app-layout>
    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            <x-flow.page-header
                :title="__('email_marketing_templates_title')"
                :description="__('email_marketing_templates_intro')"
            />

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            @if (! empty($modelTemplates))
                <div class="mt-8">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('email_marketing_template_models_heading') }}</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_template_models_intro') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($modelTemplates as $slug => $model)
                            <div class="flow-panel flex flex-col justify-between p-4">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $model['name'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $model['category'] ?? '—' }}</p>
                                </div>
                                <div class="mt-4">
                                    @if (in_array($slug, $importedModelKeys, true))
                                        <span class="inline-flex rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('email_marketing_template_model_in_workspace') }}</span>
                                    @else
                                        <form method="post" action="{{ route('email-marketing.templates.from-model', ['slug' => $slug]) }}">
                                            @csrf
                                            <x-primary-button type="submit" class="text-sm">{{ __('email_marketing_template_add_from_model') }}</x-primary-button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-10">
                <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_template_library_rubric') }}</p>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('email_marketing_your_templates') }}</h2>
                    <a href="{{ route('email-marketing.templates.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                        <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                        <span>{{ __('email_marketing_workspace_template_create') }}</span>
                    </a>
                </div>
                @if (count($categoryOptions) > 0)
                    <form method="get" action="{{ route('email-marketing.templates.index') }}" class="mt-3 flex max-w-md flex-col gap-1 sm:max-w-lg">
                        <x-input-label for="tpl_category" :value="__('email_marketing_filter_by_category')" />
                        <select
                            id="tpl_category"
                            name="category"
                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            onchange="this.form.submit()"
                        >
                            <option value="">{{ __('email_marketing_filter_all_categories') }}</option>
                            @foreach ($categoryOptions as $c)
                                <option value="{{ $c }}" @selected((string) ($category ?? '') === (string) $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                @if ($templates->isEmpty())
                    <div class="mt-4">
                        @include('email-marketing.partials.empty', ['message' => __('No templates yet.')])
                    </div>
                @else
                    <div class="mt-4 flow-panel overflow-hidden p-0">
                        <table class="min-w-full table-fixed text-start divide-y divide-slate-200 text-sm dark:divide-slate-700">
                            <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                                <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Category') }}</th>
                                    <th class="px-4 py-3 text-start">{{ __('Updated') }}</th>
                                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($templates as $t)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white text-start">{{ $t->name }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-start">{{ $t->category ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-start">{{ $t->updated_at?->diffForHumans() }}</td>
                                        <td class="px-4 py-3 text-end">
                                            <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                                <a
                                                    href="{{ route('email-marketing.templates.edit', $t) }}"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                    title="{{ __('Edit') }}"
                                                >
                                                    <i class="fa-solid fa-pen-to-square text-sm" aria-hidden="true"></i>
                                                    <span class="sr-only">{{ __('Edit') }}</span>
                                                </a>
                                                <form method="post" action="{{ route('email-marketing.templates.destroy', $t) }}" class="inline" onsubmit="return confirm(@json(__('email_marketing_workspace_template_delete_confirm')))">
                                                    @csrf
                                                    @method('delete')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-rose-200 hover:text-rose-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:text-rose-400"
                                                        title="{{ __('Remove') }}"
                                                    >
                                                        <i class="fa-regular fa-trash-can text-sm" aria-hidden="true"></i>
                                                        <span class="sr-only">{{ __('Remove') }}</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $templates->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
