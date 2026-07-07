<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
            {{ __('Projects') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('ai'))
                <div class="mb-6 rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    {{ $errors->first('ai') }}
                </div>
            @endif
            <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-emerald-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Completed') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($projectStats['completed'] ?? 0) }}</p>
                </div>
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-indigo-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Pending') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ number_format($projectStats['pending'] ?? 0) }}</p>
                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('No deadline or due after 7 days') }}</p>
                </div>
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-rose-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Past deadline') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-rose-600 dark:text-rose-400">{{ number_format($projectStats['overdue'] ?? 0) }}</p>
                </div>
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-amber-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Due within 7 days') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ number_format($projectStats['due_soon'] ?? 0) }}</p>
                </div>
            </div>

            <div
                class="mb-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end"
                @if (! empty($showAiExampleCard))
                    x-data="{ aiModalOpen: @json($errors->has('prompt') || $errors->has('ai')) }"
                @endif
            >
                @if (! empty($showAiExampleCard))
                    <div class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto sm:ms-auto">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200/90 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-indigo-500/40 dark:bg-slate-800 dark:text-indigo-200 dark:hover:bg-indigo-950/50"
                            @click="aiModalOpen = true"
                        >
                            <i class="fa-solid fa-wand-magic-sparkles text-sm text-indigo-600 dark:text-indigo-300" aria-hidden="true"></i>
                            {{ __('projects_ai_open_modal') }} ({{ $aiExampleCreditCost }} {{ __('credits') }})
                        </button>
                    </div>
                    <div
                        x-show="aiModalOpen"
                        x-cloak
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-4 sm:items-center"
                        @click.self="aiModalOpen = false"
                        @keydown.escape.window="aiModalOpen = false"
                        role="dialog"
                        aria-modal="true"
                        aria-label="{{ __('projects_ai_modal_title') }}"
                    >
                        <div
                            x-show="aiModalOpen"
                            @click.away="aiModalOpen = false"
                            x-transition:enter="ease-out duration-200"
                            x-transition:enter-start="translate-y-3 opacity-0 sm:scale-95"
                            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                            class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl dark:border-slate-600 dark:bg-slate-900 sm:p-6"
                        >
                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('projects_ai_modal_title') }}</h3>
                            <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">{{ __('projects_ai_modal_body') }}</p>
                            <form method="post" action="{{ route('projects.ai.example-workspace') }}" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <x-input-label for="projects_ai_prompt" :value="__('projects_ai_prompt_label')" />
                                    <x-ai-voice-wrap target-id="projects_ai_prompt" class="mt-1">
                                        <textarea
                                            id="projects_ai_prompt"
                                            name="prompt"
                                            rows="8"
                                            required
                                            minlength="20"
                                            maxlength="8000"
                                            class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                            placeholder="{{ __('projects_ai_prompt_placeholder') }}"
                                        >{{ old('prompt') }}</textarea>
                                    </x-ai-voice-wrap>
                                    <x-input-error :messages="$errors->get('prompt')" class="mt-2" />
                                </div>
                                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                        @click="aiModalOpen = false"
                                    >
                                        {{ __('projects_ai_modal_cancel') }}
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                                    >
                                        <i class="fa-solid fa-rocket text-xs" aria-hidden="true"></i>
                                        {{ __('projects_ai_modal_submit') }} ({{ $aiExampleCreditCost }} {{ __('credits') }})
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
                <a href="{{ route('projects.create') }}" class="inline-flex sm:shrink-0 sm:ms-0 sm:ms-auto">
                    <x-primary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                        <i class="fa-solid fa-folder-plus text-sm" aria-hidden="true"></i>
                        {{ __('New project') }}
                    </x-primary-button>
                </a>
            </div>
            <div class="flow-panel mb-8 p-6 sm:p-8">
                <form method="GET" action="{{ route('projects.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="search" :value="$q" class="mt-1 block w-full max-w-md" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 sm:min-w-[12rem]">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Enums\ProjectStatus::cases() as $case)
                                <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="source" :value="__('Source')" />
                        <select id="source" name="source" class="mt-1 block w-full rounded-lg border-slate-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 sm:min-w-[12rem]">
                            <option value="">{{ __('All') }}</option>
                            @foreach (\App\Enums\ProjectSource::cases() as $case)
                                <option value="{{ $case->value }}" @selected($source === $case->value)>{{ $case->label() }}</option>
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
                            <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Source / owner') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Team / progress') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Client') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Updated') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                        @forelse ($projects as $project)
                            @php
                                $srcLine = match (true) {
                                    $project->source === \App\Enums\ProjectSource::BusinessProvider && $project->provider => $project->provider->name,
                                    $project->source === \App\Enums\ProjectSource::FormWebsite && $project->formSubmission?->form => $project->formSubmission->form->name,
                                    default => $project->source->label(),
                                };
                                $teamLabel = $project->teamMembers->isEmpty()
                                    ? '—'
                                    : $project->teamMembers->take(3)->pluck('name')->join(', ').($project->teamMembers->count() > 3 ? ' +'.($project->teamMembers->count() - 3) : '');
                                $totalT = (int) ($project->tasks_count ?? 0);
                                $doneT = (int) ($project->tasks_done_count ?? 0);
                                $pct = $totalT > 0 ? (int) round(($doneT / $totalT) * 100) : 0;
                            @endphp
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3 font-medium text-start">
                                    <a href="{{ route('projects.show', $project) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __($project->title) }}</a>
                                </td>
                                <td class="px-4 py-3 text-start">
                                    <x-flow.badge variant="primary">{{ $project->status->label() }}</x-flow.badge>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 text-start">{{ $srcLine }}</td>
                                <td class="px-4 py-3 text-start">
                                    <div class="min-w-[8rem] space-y-1">
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $teamLabel }}</p>
                                        @if ($totalT > 0)
                                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <p class="text-[11px] tabular-nums text-slate-500 dark:text-slate-400">{{ $doneT }}/{{ $totalT }} ({{ $pct }}%)</p>
                                        @else
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ __('No tasks') }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-start">{{ $project->client?->name ?? __('None') }}</td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400 text-start">{{ $project->updated_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-end">
                                    <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                        <a
                                            href="{{ route('projects.show', $project) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('View') }}"
                                        >
                                            <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                        </a>
                                        <a
                                            href="{{ route('projects.edit', $project) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                            title="{{ __('Edit') }}"
                                        >
                                            <i class="fa-solid fa-pen-to-square text-sm" aria-hidden="true"></i>
                                        </a>
                                        <a
                                            href="{{ route('projects.tasks.kanban', $project) }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-cyan-200 hover:text-cyan-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-cyan-500/40 dark:hover:text-cyan-400"
                                            title="{{ __('Task board') }}"
                                        >
                                            <i class="fa-solid fa-table-columns text-sm" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No projects yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            <div class="mt-6">
                {{ $projects->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
