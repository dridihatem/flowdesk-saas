@php
    $start = $timeline['start'];
    $end = $timeline['end'];
    $rangeDays = $timeline['rangeDays'];
    $rows = $timeline['rows'];
    $dayHeaders = [];
    for ($d = 0; $d < $rangeDays; $d++) {
        $dayHeaders[] = $start->addDays($d);
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $project->title }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Gantt timeline') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('projects.tasks.kanban', $project) }}">
                    <x-secondary-button type="button">{{ __('Task board') }}</x-secondary-button>
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

            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Gantt hint') }}</p>

            <div class="flow-panel overflow-hidden p-0">
                @if ($rows === [])
                    <p class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No tasks yet. Add tasks from the board.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <div class="min-w-[56rem] divide-y divide-slate-200/80 dark:divide-slate-700/80">
                            <div class="flex bg-slate-50/90 dark:bg-slate-800/50">
                                <div class="sticky left-0 z-10 w-52 shrink-0 border-r border-slate-200/80 bg-slate-50/90 px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/90 dark:text-slate-400">
                                    {{ __('Task') }}
                                </div>
                                <div class="flex flex-1">
                                    @foreach ($dayHeaders as $day)
                                        <div class="min-w-0 flex-1 border-l border-slate-200/60 px-1 py-2 text-center text-[10px] font-semibold uppercase text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                                            <span class="block">{{ $day->format('M j') }}</span>
                                            <span class="font-normal text-slate-400 dark:text-slate-500">{{ $day->format('D') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @foreach ($rows as $row)
                                @php($task = $row['task'])
                                @php($status = $task->status)
                                <div class="flex items-stretch">
                                    <div class="sticky left-0 z-10 w-52 shrink-0 border-r border-slate-200/80 bg-white/95 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-900/90">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                            @if ($status)
                                                <x-flow.badge :variant="$status->badgeVariant()">{{ $status->label() }}</x-flow.badge>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $row['start']->format('Y-m-d') }} → {{ $row['end']->format('Y-m-d') }}
                                        </p>
                                    </div>
                                    <div class="relative flex-1 bg-slate-100/60 py-2 dark:bg-slate-800/50">
                                        <div class="relative h-11">
                                            <div
                                                class="absolute top-1 flex h-9 items-center rounded-lg px-2 text-xs font-semibold {{ $status?->ganttBarClass() ?? 'bg-indigo-600 text-white shadow-sm ring-1 ring-indigo-800/25' }}"
                                                style="left: {{ $row['offsetPct'] }}%; width: {{ max(2, $row['spanPct']) }}%; min-width: 2.5rem;@if ($status?->ganttBarStyle()) {{ $status->ganttBarStyle() }}@endif"
                                                title="{{ $task->title }}"
                                            >
                                                <span class="truncate">{{ $task->title }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
