@props(['project', 'active' => 'overview'])

<nav class="flex flex-wrap gap-2 border-b border-slate-200/80 pb-4 dark:border-slate-700/80">
    <a
        href="{{ route('portal.projects.show', $project) }}"
        @class([
            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' => $active === 'overview',
            'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $active !== 'overview',
        ])
    >
        <i class="fa-solid fa-circle-info text-xs opacity-80" aria-hidden="true"></i>
        {{ __('Overview') }}
    </a>
    <a
        href="{{ route('portal.projects.kanban', $project) }}"
        @class([
            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' => $active === 'kanban',
            'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $active !== 'kanban',
        ])
    >
        <i class="fa-solid fa-table-columns text-xs opacity-80" aria-hidden="true"></i>
        {{ __('Task board') }}
    </a>
    <a
        href="{{ route('portal.projects.gantt', $project) }}"
        @class([
            'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
            'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' => $active === 'gantt',
            'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' => $active !== 'gantt',
        ])
    >
        <i class="fa-solid fa-chart-gantt text-xs opacity-80" aria-hidden="true"></i>
        {{ __('Gantt timeline') }}
    </a>
</nav>
