<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $project->title }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Task board') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('projects.tasks.gantt', $project) }}">
                    <x-secondary-button type="button">{{ __('Gantt timeline') }}</x-secondary-button>
                </a>
                <a href="{{ route('projects.show', $project) }}">
                    <x-secondary-button type="button">{{ __('Project overview') }}</x-secondary-button>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-[100rem] w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first() }}</div>
            @endif

            @php($currencyOptions = flowdesk_currency_select_options($workspaceMoneyCurrency))
            <div class="flow-panel p-5 sm:p-6">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Add task') }}</h3>
                <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <div class="sm:col-span-2 lg:col-span-4">
                        <x-input-label for="new_task_title" :value="__('Task title')" class="!text-slate-600 dark:!text-slate-400" />
                        <x-text-input id="new_task_title" name="title" type="text" class="mt-1 block w-full" required :value="old('title')" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="new_task_desc" :value="__('Description')" class="!text-slate-600 dark:!text-slate-400" />
                        <textarea id="new_task_desc" name="description" rows="2" class="flow-input mt-1 block w-full text-sm">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="new_task_status" :value="__('Column')" class="!text-slate-600 dark:!text-slate-400" />
                        <select id="new_task_status" name="status" class="flow-input-select mt-1 block w-full">
                            @foreach (\App\Enums\TaskStatus::kanbanOrder() as $st)
                                <option value="{{ $st->value }}" @selected(old('status', 'todo') === $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="new_task_end" :value="__('Deadline')" class="!text-slate-600 dark:!text-slate-400" />
                        <input id="new_task_end" type="date" name="ends_on" value="{{ old('ends_on') }}" class="flow-input-select mt-1 block w-full text-sm" />
                    </div>
                    <div>
                        <x-input-label for="new_task_amount" :value="__('Final amount')" class="!text-slate-600 dark:!text-slate-400" />
                        <x-text-input id="new_task_amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full text-sm" placeholder="0.00" :value="old('amount')" />
                    </div>
                    <div>
                        <x-input-label for="new_task_currency" :value="__('Currency')" class="!text-slate-600 dark:!text-slate-400" />
                        <select id="new_task_currency" name="currency" class="flow-input-select mt-1 block w-full text-sm">
                            <option value="">{{ __('Default (:code)', ['code' => $workspaceMoneyCurrency]) }}</option>
                            @foreach ($currencyOptions as $code => $label)
                                <option value="{{ $code }}" @selected(old('currency') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end pb-1">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                            <input type="hidden" name="billable" value="0" />
                            <input type="checkbox" name="billable" value="1" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600" @checked(old('billable', '1') !== '0') />
                            {{ __('Billable') }}
                        </label>
                    </div>
                    <div class="flex items-end sm:col-span-2 lg:col-span-4">
                        <x-primary-button type="submit" class="!normal-case !tracking-normal">{{ __('Add task') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div
                class="flow-panel p-4 sm:p-5"
                data-kanban-board
                data-reorder-url="{{ route('projects.tasks.reorder', $project) }}"
            >
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach (\App\Enums\TaskStatus::kanbanOrder() as $status)
                        <div data-kanban-wrap data-status="{{ $status->value }}" class="flex min-h-[12rem] flex-col rounded-xl border p-3 {{ $status->kanbanColumnClass() }}">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-xs font-bold uppercase tracking-wide {{ $status->kanbanHeaderClass() }}">{{ $status->label() }}</h3>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold tabular-nums shadow-sm {{ $status->kanbanCountBadgeClass() }}">{{ $columns[$status->value]->count() }}</span>
                            </div>
                            <ul data-kanban-column class="min-h-[8rem] flex-1 space-y-2">
                                @foreach ($columns[$status->value] as $task)
                                    <li
                                        data-task-id="{{ $task->id }}"
                                        class="cursor-grab rounded-lg border border-slate-200/90 border-l-4 bg-white p-3 shadow-sm active:cursor-grabbing dark:border-slate-600/60 dark:bg-slate-900/80 {{ $task->status?->kanbanCardAccentClass() ?? '' }}"
                                    >
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                            @if ($task->status)
                                                <x-flow.badge :variant="$task->status->badgeVariant()">{{ $task->status->label() }}</x-flow.badge>
                                            @endif
                                        </div>
                                        @if ($task->description)
                                            <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $task->description }}</p>
                                        @endif
                                        <div class="mt-1 flex flex-wrap gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                            @if ($task->ends_on)
                                                <span class="{{ $task->isOverdue() ? 'font-semibold text-amber-700 dark:text-amber-300' : '' }}">{{ $task->ends_on->format('Y-m-d') }}</span>
                                            @endif
                                            @if (! $task->billable)
                                                <span>{{ __('Non-billable') }}</span>
                                            @endif
                                            @if ($task->formattedAmount($workspaceMoneyCurrency))
                                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $task->formattedAmount($workspaceMoneyCurrency) }}</span>
                                            @endif
                                        </div>
                                        <details class="mt-2 group">
                                            <summary class="cursor-pointer text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Edit') }}</summary>
                                            <form method="POST" action="{{ route('projects.tasks.update', [$project, $task]) }}" class="mt-2 space-y-2 border-t border-slate-200/80 pt-2 dark:border-slate-600/50">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <x-input-label for="task_{{ $task->id }}_title" :value="__('Task title')" class="!text-xs" />
                                                    <x-text-input id="task_{{ $task->id }}_title" name="title" type="text" class="mt-1 block w-full text-sm" :value="$task->title" required />
                                                </div>
                                                <div>
                                                    <x-input-label for="task_{{ $task->id }}_desc" :value="__('Description')" class="!text-xs" />
                                                    <textarea id="task_{{ $task->id }}_desc" name="description" rows="2" class="flow-input mt-1 block w-full text-sm">{{ $task->description }}</textarea>
                                                </div>
                                                <div>
                                                    <x-input-label for="task_{{ $task->id }}_status" :value="__('Status')" class="!text-xs" />
                                                    <select id="task_{{ $task->id }}_status" name="status" class="flow-input-select mt-1 block w-full text-sm">
                                                        @foreach (\App\Enums\TaskStatus::cases() as $st)
                                                            <option value="{{ $st->value }}" @selected($task->status === $st)>{{ $st->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <div>
                                                        <x-input-label for="task_{{ $task->id }}_start" :value="__('Start date')" class="!text-xs" />
                                                        <input id="task_{{ $task->id }}_start" type="date" name="starts_on" value="{{ $task->starts_on?->format('Y-m-d') }}" class="flow-input-select mt-1 block w-full text-sm" />
                                                    </div>
                                                    <div>
                                                        <x-input-label for="task_{{ $task->id }}_end" :value="__('Deadline')" class="!text-xs" />
                                                        <input id="task_{{ $task->id }}_end" type="date" name="ends_on" value="{{ $task->ends_on?->format('Y-m-d') }}" class="flow-input-select mt-1 block w-full text-sm" />
                                                    </div>
                                                </div>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <div>
                                                        <x-input-label for="task_{{ $task->id }}_amount" :value="__('Final amount')" class="!text-xs" />
                                                        <x-text-input id="task_{{ $task->id }}_amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full text-sm" :value="$task->amount_cents !== null ? flowdesk_major_amount_for_input((int) $task->amount_cents, $task->displayCurrency($workspaceMoneyCurrency)) : ''" />
                                                    </div>
                                                    <div>
                                                        <x-input-label for="task_{{ $task->id }}_currency" :value="__('Currency')" class="!text-xs" />
                                                        <select id="task_{{ $task->id }}_currency" name="currency" class="flow-input-select mt-1 block w-full text-sm">
                                                            <option value="">{{ __('Default (:code)', ['code' => $workspaceMoneyCurrency]) }}</option>
                                                            @foreach ($currencyOptions as $code => $label)
                                                                <option value="{{ $code }}" @selected($task->currency === $code)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-700 dark:text-slate-200">
                                                    <input type="hidden" name="billable" value="0" />
                                                    <input type="checkbox" name="billable" value="1" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600" @checked($task->billable) />
                                                    {{ __('Billable') }}
                                                </label>
                                                <x-secondary-button type="submit" class="!py-1.5 !text-xs !normal-case">{{ __('Save task') }}</x-secondary-button>
                                            </form>
                                        </details>
                                        <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" class="mt-2" onsubmit="return confirm({{ json_encode(__('Delete this task?')) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-500 dark:text-rose-400">{{ __('Delete') }}</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/project-kanban.js')
</x-app-layout>
