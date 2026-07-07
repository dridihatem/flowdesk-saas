<div class="space-y-8">
    <div>
        <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
            <i class="fa-solid fa-list-check text-emerald-500" aria-hidden="true"></i>
            {{ __('Add task') }}
        </h3>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Choose whether the task is in the project scope or extra work, and whether its price is bundled in the project total or billed as its own line when billable.') }}</p>
        <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="pt_title" :value="__('Task title')" />
                <x-text-input id="pt_title" name="title" type="text" class="mt-1 block w-full" required :value="old('title')" />
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="pt_desc" :value="__('Description')" />
                <textarea id="pt_desc" name="description" rows="3" class="flow-input mt-1 block w-full">{{ old('description') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" />
            </div>
            <div>
                <x-input-label for="pt_status" :value="__('Column')" />
                <select id="pt_status" name="status" class="flow-input-select mt-1 block w-full">
                    @foreach (\App\Enums\TaskStatus::kanbanOrder() as $st)
                        <option value="{{ $st->value }}" @selected(old('status', 'todo') === $st->value)>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="pt_start" :value="__('Start date')" />
                <input id="pt_start" type="date" name="starts_on" value="{{ old('starts_on') }}" class="flow-input-select mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="pt_end" :value="__('Deadline')" />
                <input id="pt_end" type="date" name="ends_on" value="{{ old('ends_on') }}" class="flow-input-select mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="pt_scope" :value="__('Task scope')" />
                <select id="pt_scope" name="scope" class="flow-input-select mt-1 block w-full">
                    @foreach (\App\Enums\TaskScope::cases() as $sc)
                        <option value="{{ $sc->value }}" @selected(old('scope', 'core') === $sc->value)>{{ $sc->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="pt_price_mode" :value="__('Pricing on invoice')" />
                <select id="pt_price_mode" name="price_mode" class="flow-input-select mt-1 block w-full">
                    @foreach (\App\Enums\TaskPriceMode::cases() as $pm)
                        <option value="{{ $pm->value }}" @selected(old('price_mode', 'bundled') === $pm->value)>{{ $pm->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="pt_amount" :value="__('Final amount')" />
                <x-text-input id="pt_amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full" placeholder="0.00" :value="old('amount')" />
            </div>
            <div>
                <x-input-label for="pt_currency" :value="__('Currency')" />
                <select id="pt_currency" name="currency" class="flow-input-select mt-1 block w-full">
                    <option value="">{{ __('Default (:code)', ['code' => $workspaceMoneyCurrency]) }}</option>
                    @foreach ($currencyOptions as $code => $label)
                        <option value="{{ $code }}" @selected(old('currency') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end pb-1">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="hidden" name="billable" value="0" />
                    <input type="checkbox" name="billable" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600" @checked(old('billable', '1') !== '0') />
                    {{ __('Billable') }}
                </label>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <x-primary-button type="submit">{{ __('Create task') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-600/50">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                    <i class="fa-solid fa-diagram-project text-violet-500" aria-hidden="true"></i>
                    {{ __('Tasks & deliverables') }}
                </h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Deadlines, billing, attachments per task') }}</p>
            </div>
        </div>
        <ul class="mt-6 space-y-4">
            @forelse ($project->tasks as $task)
                <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 p-5 shadow-sm dark:border-slate-600/60 dark:from-slate-900/80 dark:to-slate-950/60">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-semibold text-slate-900 dark:text-white">{{ $task->title }}</h4>
                                <x-flow.badge variant="primary">{{ $task->status->label() }}</x-flow.badge>
                                @if ($task->billable)
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">{{ __('Billable') }}</span>
                                @else
                                    <span class="rounded-full bg-slate-500/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">{{ __('Non-billable') }}</span>
                                @endif
                                <span class="rounded-full bg-indigo-500/15 px-2 py-0.5 text-[11px] font-medium text-indigo-900 dark:text-indigo-100">{{ $task->scope->label() }}</span>
                                <span class="rounded-full bg-violet-500/15 px-2 py-0.5 text-[11px] font-medium text-violet-900 dark:text-violet-100">{{ $task->price_mode->label() }}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600 dark:text-slate-300">
                                @if ($task->ends_on)
                                    <span class="inline-flex items-center gap-1.5">
                                        <i class="fa-regular fa-calendar text-slate-400" aria-hidden="true"></i>
                                        <span class="{{ $task->isOverdue() ? 'font-semibold text-amber-700 dark:text-amber-300' : '' }}">{{ __('Deadline: :date', ['date' => $task->ends_on->format('Y-m-d')]) }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400">{{ __('No deadline') }}</span>
                                @endif
                                @if ($task->formattedAmount($workspaceMoneyCurrency))
                                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-800 dark:text-slate-100">
                                        <i class="fa-solid fa-coins text-slate-400" aria-hidden="true"></i>
                                        {{ $task->formattedAmount($workspaceMoneyCurrency) }}
                                    </span>
                                @endif
                            </div>
                            <div
                                class="task-work-row mt-3 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200/70 bg-white/60 px-3 py-2 dark:border-slate-600/50 dark:bg-slate-900/30"
                                data-task-id="{{ $task->id }}"
                                data-start-url="{{ route('projects.tasks.tracking.start', [$project, $task]) }}"
                                data-pause-url="{{ route('projects.tasks.tracking.pause', [$project, $task]) }}"
                                data-running="{{ $task->tracking_started_at ? '1' : '0' }}"
                                data-started-at="{{ $task->tracking_started_at?->toIso8601String() ?? '' }}"
                                data-accumulated="{{ (int) $task->tracking_accumulated_seconds }}"
                            >
                                <span class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Work timer') }}</span>
                                <span class="task-elapsed font-mono text-sm font-semibold tabular-nums text-slate-900 dark:text-white">0:00:00</span>
                                <button type="button" class="task-start inline-flex items-center gap-1 rounded-lg bg-amber-500/90 px-2.5 py-1 text-xs font-medium text-amber-950 hover:bg-amber-400 dark:bg-amber-600/90 dark:text-amber-50 dark:hover:bg-amber-500">
                                    <i class="fa-solid fa-play text-[10px]" aria-hidden="true"></i>
                                    {{ __('Start timer') }}
                                </button>
                                <button type="button" class="task-pause hidden inline-flex items-center gap-1 rounded-lg bg-slate-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-slate-500 dark:bg-slate-700 dark:hover:bg-slate-600">
                                    <i class="fa-solid fa-pause text-[10px]" aria-hidden="true"></i>
                                    {{ __('Pause timer') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @if ($task->description)
                        <div class="mt-3 rounded-lg border border-slate-200/60 bg-white/70 px-3 py-2 text-sm  text-slate-700 dark:border-slate-600/50 dark:bg-slate-800/40 dark:text-slate-200">
                            {{ trim($task->description) }}
                        </div>
                    @endif
                    <div class="mt-4 border-t border-slate-200/70 pt-4 dark:border-slate-600/50">
                        <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Task files') }}</p>
                        @if ($task->files->isEmpty())
                            <p class="mt-1 text-sm text-slate-500">{{ __('No files yet.') }}</p>
                        @else
                            <ul class="mt-2 space-y-2">
                                @foreach ($task->files as $tf)
                                    <li class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200/60 bg-white/50 p-2 text-sm dark:border-slate-600/40 dark:bg-slate-900/30">
                                        @if ($tf->isImage())
                                            <button
                                                type="button"
                                                class="shrink-0 overflow-hidden rounded-md ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:ring-slate-600"
                                                @click="$dispatch('flowdesk-file-preview', { src: @js($tf->url()), title: @js($tf->original_name) })"
                                            >
                                                @if ($tf->thumbUrl())
                                                    <img src="{{ $tf->thumbUrl() }}" alt="" class="h-12 w-12 object-cover" loading="lazy" />
                                                @else
                                                    <img src="{{ $tf->url() }}" alt="" class="h-12 w-12 object-cover" loading="lazy" />
                                                @endif
                                            </button>
                                        @else
                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-slate-100 text-lg text-slate-500 dark:bg-slate-800" aria-hidden="true">
                                                <i class="fa-regular fa-file-lines"></i>
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ $tf->url() }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 dark:text-indigo-400">{{ $tf->original_name }}</a>
                                            <span class="ms-2 text-xs text-slate-500">{{ $tf->categoryEnum()->label() }}</span>
                                        </div>
                                        <form method="POST" action="{{ route('projects.tasks.files.destroy', [$project, $task, $tf]) }}" class="shrink-0" onsubmit="return confirm({{ json_encode(__('Remove this file?')) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:underline dark:text-rose-400">{{ __('Remove') }}</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <form method="POST" action="{{ route('projects.tasks.files.store', [$project, $task]) }}" enctype="multipart/form-data" class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end">
                            @csrf
                            <div>
                                <label class="mb-1 block text-xs text-slate-500 dark:text-slate-400" for="tf_cat_{{ $task->id }}">{{ __('Category') }}</label>
                                <select id="tf_cat_{{ $task->id }}" name="category" class="flow-input-select block w-full text-sm" required>
                                    @foreach (\App\Enums\ProjectFileCategory::cases() as $cat)
                                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0 flex-1 sm:min-w-[180px]">
                                <label class="sr-only" for="tf_{{ $task->id }}">{{ __('Upload file') }}</label>
                                <input id="tf_{{ $task->id }}" type="file" name="file" required class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-slate-700 dark:file:text-indigo-200" />
                            </div>
                            <x-secondary-button type="submit" class="!text-xs !normal-case">{{ __('Attach') }}</x-secondary-button>
                        </form>
                    </div>
                    @include('projects.partials.task-comments', ['project' => $project, 'task' => $task])
                    <details class="mt-4 group">
                        <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Edit task') }}</summary>
                        <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}" class="mt-3 space-y-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                            @csrf
                            @method('PATCH')
                            <div>
                                <x-input-label :for="'ev_title_'.$task->id" :value="__('Task title')" class="!text-xs" />
                                <x-text-input :id="'ev_title_'.$task->id" name="title" type="text" class="mt-1 block w-full text-sm" :value="$task->title" required />
                            </div>
                            <div>
                                <x-input-label :for="'ev_desc_'.$task->id" :value="__('Description')" class="!text-xs" />
                                <textarea :id="'ev_desc_'.$task->id" name="description" rows="3" class="flow-input mt-1 block w-full text-sm">{{ $task->description }}</textarea>
                            </div>
                            <div>
                                <x-input-label :for="'ev_st_'.$task->id" :value="__('Status')" class="!text-xs" />
                                <select :id="'ev_st_'.$task->id" name="status" class="flow-input-select mt-1 block w-full text-sm">
                                    @foreach (\App\Enums\TaskStatus::cases() as $st)
                                        <option value="{{ $st->value }}" @selected($task->status === $st)>{{ $st->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div>
                                    <x-input-label :for="'ev_s_'.$task->id" :value="__('Start date')" class="!text-xs" />
                                    <input :id="'ev_s_'.$task->id" type="date" name="starts_on" value="{{ $task->starts_on?->format('Y-m-d') }}" class="flow-input-select mt-1 block w-full text-sm" />
                                </div>
                                <div>
                                    <x-input-label :for="'ev_e_'.$task->id" :value="__('Deadline')" class="!text-xs" />
                                    <input :id="'ev_e_'.$task->id" type="date" name="ends_on" value="{{ $task->ends_on?->format('Y-m-d') }}" class="flow-input-select mt-1 block w-full text-sm" />
                                </div>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div>
                                    <x-input-label :for="'ev_scope_'.$task->id" :value="__('Task scope')" class="!text-xs" />
                                    <select :id="'ev_scope_'.$task->id" name="scope" class="flow-input-select mt-1 block w-full text-sm">
                                        @foreach (\App\Enums\TaskScope::cases() as $sc)
                                            <option value="{{ $sc->value }}" @selected($task->scope === $sc)>{{ $sc->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label :for="'ev_pm_'.$task->id" :value="__('Pricing on invoice')" class="!text-xs" />
                                    <select :id="'ev_pm_'.$task->id" name="price_mode" class="flow-input-select mt-1 block w-full text-sm">
                                        @foreach (\App\Enums\TaskPriceMode::cases() as $pm)
                                            <option value="{{ $pm->value }}" @selected($task->price_mode === $pm)>{{ $pm->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <div>
                                    <x-input-label :for="'ev_am_'.$task->id" :value="__('Final amount')" class="!text-xs" />
                                    <x-text-input :id="'ev_am_'.$task->id" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full text-sm" :value="$task->amount_cents !== null ? flowdesk_major_amount_for_input((int) $task->amount_cents, $task->displayCurrency($workspaceMoneyCurrency)) : ''" />
                                </div>
                                <div>
                                    <x-input-label :for="'ev_cur_'.$task->id" :value="__('Currency')" class="!text-xs" />
                                    <select :id="'ev_cur_'.$task->id" name="currency" class="flow-input-select mt-1 block w-full text-sm">
                                        <option value="">{{ __('Default (:code)', ['code' => $workspaceMoneyCurrency]) }}</option>
                                        @foreach ($currencyOptions as $code => $label)
                                            <option value="{{ $code }}" @selected($task->currency === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                <input type="hidden" name="billable" value="0" />
                                <input type="checkbox" name="billable" value="1" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600" @checked($task->billable) />
                                {{ __('Billable') }}
                            </label>
                            <x-secondary-button type="submit" class="!py-2 !text-xs !normal-case">{{ __('Save task') }}</x-secondary-button>
                        </form>
                    </details>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-slate-300/80 px-6 py-10 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                    {{ __('No tasks yet. Add one above or open the task board.') }}
                </li>
            @endforelse
        </ul>
    </div>
</div>
