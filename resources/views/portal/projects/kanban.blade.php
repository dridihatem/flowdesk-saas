<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $project->title }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Task board') }}</h2>
            </div>
            <a href="{{ route('portal.projects.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300">{{ __('Back to projects') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-[100rem] w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="flow-panel p-5">
                @include('portal.partials.project-nav', ['project' => $project, 'active' => 'kanban'])
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('portal_kanban_readonly_hint') }}</p>
            </div>

            <div class="flow-panel p-4 sm:p-5">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach (\App\Enums\TaskStatus::kanbanOrder() as $status)
                        <div class="flex min-h-[12rem] flex-col rounded-xl border p-3 {{ $status->kanbanColumnClass() }}">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-xs font-bold uppercase tracking-wide {{ $status->kanbanHeaderClass() }}">{{ $status->label() }}</h3>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold tabular-nums shadow-sm {{ $status->kanbanCountBadgeClass() }}">{{ $columns[$status->value]->count() }}</span>
                            </div>
                            <ul class="min-h-[8rem] flex-1 space-y-2">
                                @foreach ($columns[$status->value] as $task)
                                    <li class="rounded-lg border border-slate-200/90 border-l-4 bg-white p-3 shadow-sm dark:border-slate-600/60 dark:bg-slate-900/80 {{ $task->status?->kanbanCardAccentClass() ?? '' }}">
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
                                        </div>
                                        @include('portal.partials.task-comments', ['project' => $project, 'task' => $task])
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
