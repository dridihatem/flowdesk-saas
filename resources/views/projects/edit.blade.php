@php
    $selectedTeam = old('team_user_ids', $project->teamMembers->pluck('id')->map(fn ($id) => (int) $id)->all());
    $initialEditTab = 'details';
    if ($errors->hasAny(['final_price', 'negotiated_price', 'final_deadline'])) {
        $initialEditTab = 'pricing';
    }
    $cur = $projectMoneyCurrency;
    $tasksCollection = $project->tasks;
    $taskCount = $tasksCollection->count();
    $doneCount = $tasksCollection->filter(fn ($t) => $t->status === \App\Enums\TaskStatus::Done)->count();
@endphp
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@endpush
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex min-w-0 flex-col gap-1">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Edit project') }}</h2>
            <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ __($project->title) }}</p>
        </div>
    </x-slot>

    <div
        class="py-10"
        x-data='{
            tab: @json($initialEditTab),
            descriptionBooted: false,
            bootSummernote() {
                this.$nextTick(() => {
                    if (this.descriptionBooted) {
                        return;
                    }
                    const jq = window.jQuery;
                    if (!jq || !jq("#description").length) {
                        return;
                    }
                    if (jq("#description").next(".note-editor").length) {
                        this.descriptionBooted = true;
                        return;
                    }
                    jq("#description").summernote({
                        height: 300,
                        dialogsInBody: true,
                        placeholder: @json(__('Describe scope, deliverables, and internal notes…')),
                    });
                    this.descriptionBooted = true;
                });
            },
        }'
        x-init="$watch('tab', t => { if (t === 'details') bootSummernote() }); if (tab === 'details') { bootSummernote() }"
    >
        <div class="mx-auto max-w-8xl w-full space-y-6 sm:px-6 lg:px-8">
            <div
                class="flow-panel border border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/40"
            >
                {{-- Tab bar --}}
                <div
                    class="flex flex-col gap-4 border-b border-slate-200/80 p-4 sm:p-5 dark:border-slate-700/60 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ __('project_edit_subtitle') }}
                    </p>
                    <nav
                        class="inline-flex w-full max-w-2xl flex-col gap-1 rounded-2xl border border-slate-200/90 bg-slate-50/90 p-1.5 sm:w-auto sm:flex-row sm:items-center sm:justify-end dark:border-slate-600/50 dark:bg-slate-800/50"
                        aria-label="{{ __('Project sections') }}"
                    >
                        <button
                            type="button"
                            @click="tab = 'details'; bootSummernote();"
                            :class="tab === 'details'
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-white dark:ring-slate-600/80'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        >
                            <i class="fa-regular fa-file-lines w-4 text-center opacity-80" aria-hidden="true"></i>
                            <span>{{ __('Project details') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="tab = 'pricing'"
                            :class="tab === 'pricing'
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-white dark:ring-slate-600/80'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        >
                            <i class="fa-solid fa-sack-dollar w-4 text-center opacity-80" aria-hidden="true"></i>
                            <span>{{ __('Pricing & deadline') }}</span>
                        </button>
                        <button
                            type="button"
                            @click="tab = 'tasks'"
                            :class="tab === 'tasks'
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-white dark:ring-slate-600/80'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        >
                            <i class="fa-solid fa-list-check w-4 text-center opacity-80" aria-hidden="true"></i>
                            <span>{{ __('Tasks') }} @if($taskCount > 0)<span class="ms-0.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-md bg-slate-200/90 px-1 text-xs font-bold tabular-nums text-slate-700 dark:bg-slate-600/50 dark:text-slate-100">{{ $taskCount }}</span>@endif</span>
                        </button>
                    </nav>
                </div>

                <form
                    method="POST"
                    action="{{ route('projects.update', $project) }}"
                    enctype="multipart/form-data"
                    class="p-4 sm:p-6 lg:p-8"
                >
                    @csrf
                    @method('PUT')

                    <div x-show="tab === 'details'" class="space-y-8">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Project basics') }}</h3>
                            <div class="mt-4 space-y-5">
                                <div>
                                    <x-input-label for="title" :value="__('Title')" />
                                    <x-text-input
                                        id="title"
                                        name="title"
                                        type="text"
                                        class="mt-2 block w-full"
                                        :value="old('title', $project->title)"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                </div>
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="status" :value="__('Status')" />
                                        <select id="status" name="status" class="flow-input-select mt-2 block w-full">
                                            @foreach (\App\Enums\ProjectStatus::cases() as $case)
                                                <option
                                                    value="{{ $case->value }}"
                                                    @selected(old('status', $project->status->value) === $case->value)
                                                >{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="source" :value="__('Source')" />
                                        <select id="source" name="source" class="flow-input-select mt-2 block w-full">
                                            @foreach (\App\Enums\ProjectSource::cases() as $case)
                                                <option
                                                    value="{{ $case->value }}"
                                                    @selected(old('source', $project->source->value) === $case->value)
                                                >{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('source')" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-200/80 pt-8 dark:border-slate-700/60">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('People & relationships') }}</h3>
                            <div class="mt-4 space-y-5">
                                <div>
                                    <x-project-client-quick-add>
                                        <div>
                                            <x-input-label for="client_id" :value="__('Client')" />
                                            <select id="client_id" name="client_id" class="flow-input-select mt-2 block w-full">
                                                <option value="">{{ __('None') }}</option>
                                                @foreach ($clients as $client)
                                                    <option
                                                        value="{{ $client->id }}"
                                                        @selected(old('client_id', $project->client_id) === $client->id)
                                                    >{{ $client->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </x-project-client-quick-add>
                                </div>
                                <div>
                                    <x-input-label for="provider_id" :value="__('Provider (optional)')" />
                                    <select id="provider_id" name="provider_id" class="flow-input-select mt-2 block w-full">
                                        <option value="">{{ __('None') }}</option>
                                        @foreach ($providers as $provider)
                                            <option
                                                value="{{ $provider->id }}"
                                                @selected(old('provider_id', $project->provider_id) === $provider->id)
                                            >{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <fieldset
                                    class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 dark:border-slate-600/50 dark:bg-slate-800/30"
                                >
                                    <legend class="px-1 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('Assign team') }}</legend>
                                    <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('Select workspace members responsible for this project.') }}
                                    </p>
                                    <div class="max-h-52 space-y-1 overflow-y-auto pr-1">
                                        @foreach ($teamUsers as $user)
                                            <label
                                                class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-2 hover:bg-white/90 dark:hover:bg-slate-800/60"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="team_user_ids[]"
                                                    value="{{ $user->id }}"
                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                                                    @checked(in_array((int) $user->id, array_map('intval', (array) $selectedTeam), true))
                                                />
                                                <span class="text-sm text-slate-800 dark:text-slate-200">
                                                    {{ $user->name }} <span class="text-slate-500">({{ $user->email }})</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <div class="border-t border-slate-200/80 pt-8 dark:border-slate-700/60">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Details & files') }}</h3>
                            <div class="mt-4 space-y-5">
                                <div>
                                    <div class="flex flex-wrap items-end justify-between gap-2">
                                        <x-input-label for="description" :value="__('Description')" />
                                        <x-project-description-ai textarea-id="description" />
                                    </div>
                                    <textarea id="description" name="description" class="mt-2 block w-full">{{ old('description', $project->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>
                                <div
                                    class="rounded-2xl border border-slate-200/80 border-dashed bg-slate-50/30 p-4 dark:border-slate-600/50 dark:bg-slate-800/20"
                                >
                                    <x-input-label for="attachment" :value="__('Add another file (optional)')" />
                                    <input
                                        id="attachment"
                                        type="file"
                                        name="attachment"
                                        class="mt-2 block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-200"
                                    />
                                    <div class="mt-3">
                                        <x-input-label for="attachment_category" :value="__('File category')" />
                                        <select
                                            id="attachment_category"
                                            name="attachment_category"
                                            class="flow-input-select mt-1 block w-full text-sm"
                                        >
                                            @foreach (\App\Enums\ProjectFileCategory::cases() as $cat)
                                                <option
                                                    value="{{ $cat->value }}"
                                                    @selected(old('attachment_category', 'document') === $cat->value)
                                                >{{ $cat->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                                    <x-input-error :messages="$errors->get('attachment_category')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'pricing'" x-cloak class="space-y-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div
                                class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/90 to-white p-5 dark:border-emerald-900/30 dark:from-emerald-950/25 dark:to-slate-900/40"
                            >
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800/90 dark:text-emerald-200/90">
                                    {{ __('Final price') }}
                                </p>
                                <p class="mt-1 text-xs text-emerald-800/80 dark:text-emerald-200/80">{{ __('Internal estimate; optional.') }}</p>
                                <x-text-input
                                    id="final_price"
                                    name="final_price"
                                    type="text"
                                    inputmode="decimal"
                                    class="mt-3 block w-full flowdesk-amount"
                                    :value="old('final_price', $project->final_price !== null ? flowdesk_major_amount_for_input((int) $project->final_price, $cur) : '')"
                                />
                                <x-input-error :messages="$errors->get('final_price')" class="mt-2" />
                            </div>
                            <div
                                class="relative overflow-hidden rounded-2xl border border-violet-200/80 bg-gradient-to-br from-violet-50/90 to-white p-5 dark:border-violet-900/30 dark:from-violet-950/25 dark:to-slate-900/40"
                            >
                                <p class="text-xs font-bold uppercase tracking-wider text-violet-800/90 dark:text-violet-200/90">
                                    {{ __('Negotiated price') }}
                                </p>
                                <p class="mt-1 text-xs text-violet-800/80 dark:text-violet-200/80">{{ __('Agreed with the client, when set.') }}</p>
                                <x-text-input
                                    id="negotiated_price"
                                    name="negotiated_price"
                                    type="text"
                                    inputmode="decimal"
                                    class="mt-3 block w-full flowdesk-amount"
                                    :value="old('negotiated_price', $project->negotiated_price !== null ? flowdesk_major_amount_for_input((int) $project->negotiated_price, $cur) : '')"
                                />
                                <x-input-error :messages="$errors->get('negotiated_price')" class="mt-2" />
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            <i class="fa-solid fa-coins me-1 text-amber-500" aria-hidden="true"></i>
                            {{ __('Amounts in :cur (workspace default currency).', ['cur' => $cur]) }}
                        </p>
                        <div class="max-w-md">
                            <x-input-label for="final_deadline" :value="__('Final deadline')" />
                            <input
                                id="final_deadline"
                                type="date"
                                name="final_deadline"
                                value="{{ old('final_deadline', $project->final_deadline?->format('Y-m-d')) }}"
                                class="flow-input-select mt-2 block w-full"
                            />
                            <x-input-error :messages="$errors->get('final_deadline')" class="mt-2" />
                        </div>
                    </div>

                    <div x-show="tab === 'tasks'" x-cloak class="space-y-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Tasks') }}</h3>
                                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                                    @if ($taskCount > 0)
                                        {{ __('project_edit_task_count_line', ['total' => $taskCount, 'done' => $doneCount]) }}
                                    @else
                                        {{ __('No tasks yet for this project.') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('projects.tasks.kanban', $project) }}">
                                    <x-secondary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                                        <i class="fa-solid fa-table-columns" aria-hidden="true"></i>
                                        {{ __('Task board') }}
                                    </x-secondary-button>
                                </a>
                                <a href="{{ route('projects.show', $project) }}">
                                    <x-secondary-button type="button" class="inline-flex items-center gap-2 !normal-case">
                                        <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                                        {{ __('View project') }}
                                    </x-secondary-button>
                                </a>
                            </div>
                        </div>

                        @if ($taskCount === 0)
                            <div
                                class="rounded-2xl border border-slate-200/80 border-dashed bg-slate-50/50 p-10 text-center dark:border-slate-600/50 dark:bg-slate-800/30"
                            >
                                <i class="fa-regular fa-rectangle-list text-3xl text-slate-300 dark:text-slate-500" aria-hidden="true"></i>
                                <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-300">{{ __('No tasks to list') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ __('Add tasks from the project page or the task board.') }}</p>
                            </div>
                        @else
                            <div
                                class="overflow-x-auto rounded-2xl border border-slate-200/80 dark:border-slate-600/50"
                            >
                                <table class="min-w-full table-fixed text-start divide-y divide-slate-200/80 text-sm dark:divide-slate-700/60">
                                    <thead>
                                        <tr
                                            class="bg-slate-50/80 text-start text-xs font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400"
                                        >
                                            <th class="px-4 py-3 text-start">#</th>
                                            <th class="px-4 py-3 text-start">{{ __('Task') }}</th>
                                            <th class="px-4 py-3 whitespace-nowrap text-start">{{ __('Status') }}</th>
                                            <th class="hidden px-4 py-3 sm:table-cell text-start">{{ __('Invoicing') }}</th>
                                            <th class="hidden px-4 py-3 md:table-cell whitespace-nowrap text-start">{{ __('Line price') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200/60 bg-white/80 dark:divide-slate-700/50 dark:bg-slate-900/30">
                                        @foreach ($tasksCollection as $i => $task)
                                            <tr class="text-slate-800 dark:text-slate-200">
                                                <td class="px-4 py-3 text-slate-500 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $task->sort_order ?? $i + 1 }}</span></td>
                                                <td class="px-4 py-3 text-start">
                                                    <span class="font-medium">{{ $task->title }}</span>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-start">
                                                    <span
                                                        class="inline-flex rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                                    >{{ $task->status->label() }}</span>
                                                </td>
                                                <td class="hidden px-4 py-3 sm:table-cell text-xs text-start">
                                                    <div class="max-w-[12rem]">
                                                        <p class="truncate font-medium text-slate-700 dark:text-slate-200" title="{{ $task->price_mode->label() }}">
                                                            {{ $task->price_mode->label() }}
                                                        </p>
                                                        @if ($task->billable)
                                                            <span class="text-emerald-700 dark:text-emerald-300">{{ __('Billable') }}</span>
                                                        @else
                                                            <span class="text-slate-500 dark:text-slate-400">{{ __('Non-billable') }}</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="hidden px-4 py-3 text-end md:table-cell text-xs text-slate-600 dark:text-slate-300">
                                                    @if ($task->amount_cents !== null && (int) $task->amount_cents > 0)
                                                        <span class="flowdesk-ltr-num tabular-nums">{{ $task->formattedAmount($cur) }}</span>
                                                    @else
                                                        <span class="text-slate-400">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('project_edit_tasks_readonly_hint') }}
                            </p>
                        @endif
                    </div>

                    <div
                        class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 pt-6 dark:border-slate-700/60"
                    >
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <i class="fa-solid fa-info-circle me-1" aria-hidden="true"></i>
                            {{ __('project_edit_save_hint') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('projects.show', $project) }}"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                            >{{ __('Cancel') }}</a>
                            <x-primary-button type="submit" class="!normal-case !tracking-normal px-6">
                                <i class="fa-solid fa-floppy-disk me-2 text-sm" aria-hidden="true"></i>
                                {{ __('Save') }}
                            </x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
