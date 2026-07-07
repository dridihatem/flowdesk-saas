<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Client portal') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('My projects') }}</h2>
            </div>
            <a href="{{ route('portal.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to portal') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-indigo-50/80 via-white to-white px-5 py-4 dark:border-slate-700/80 dark:from-indigo-950/30 dark:via-slate-900/50 dark:to-slate-900/40">
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('portal_projects_intro') }}</p>
                </div>

                <div class="border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/80">
                    <form method="GET" action="{{ route('portal.projects.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <div class="min-w-0 flex-1">
                            <x-input-label for="project_search" :value="__('Search')" class="!text-xs" />
                            <div class="relative mt-1">
                                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                                <x-text-input id="project_search" name="q" type="search" class="block w-full ps-9" :value="$q" placeholder="{{ __('portal_projects_search_placeholder') }}" />
                            </div>
                        </div>
                        <div class="w-full sm:w-48">
                            <x-input-label for="project_status" :value="__('Status')" class="!text-xs" />
                            <select id="project_status" name="status" class="flow-input-select mt-1 block w-full text-sm">
                                <option value="">{{ __('All statuses') }}</option>
                                @foreach (\App\Enums\ProjectStatus::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-primary-button type="submit" class="inline-flex items-center gap-2 !normal-case">
                                <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
                                {{ __('Filter') }}
                            </x-primary-button>
                            @if ($q !== '' || ($status ?? '') !== '')
                                <a href="{{ route('portal.projects.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:bg-slate-800/40 dark:text-slate-400">
                                <th class="px-5 py-3 text-start">{{ __('Project') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Progress') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Provider') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Deadline') }}</th>
                                <th class="px-5 py-3 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($projects as $project)
                                @php
                                    $taskTotal = (int) ($project->tasks_count ?? 0);
                                    $taskDone = (int) ($project->done_tasks_count ?? 0);
                                    $pct = $taskTotal > 0 ? (int) round(($taskDone / $taskTotal) * 100) : 0;
                                @endphp
                                <tr class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                    <td class="px-5 py-4 text-start">
                                        <a href="{{ route('portal.projects.show', $project) }}" class="font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">{{ $project->title }}</a>
                                    </td>
                                    <td class="px-5 py-4 text-start">
                                        @if ($project->status)
                                            <x-flow.badge :variant="$project->status->badgeVariant()">{{ $project->status->label() }}</x-flow.badge>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-start">
                                        @if ($taskTotal > 0)
                                            <div class="flex min-w-[7rem] items-center gap-2">
                                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                    <div class="h-full rounded-full bg-indigo-600 dark:bg-indigo-400" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <span class="shrink-0 text-xs tabular-nums text-slate-500">{{ $pct }}%</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300 text-start">{{ $project->provider?->name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-slate-700 dark:text-slate-300 text-start"><span class="flowdesk-ltr-num tabular-nums">{{ $project->final_deadline?->format('Y-m-d') ?? '—' }}</span></td>
                                    <td class="px-5 py-4 text-end">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            <a href="{{ route('portal.projects.show', $project) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400" title="{{ __('View') }}">
                                                <span class="sr-only">{{ __('View') }}</span>
                                                <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                            </a>
                                            <a href="{{ route('portal.projects.kanban', $project) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400" title="{{ __('Kanban') }}">
                                                <span class="sr-only">{{ __('Kanban') }}</span>
                                                <i class="fa-solid fa-table-columns text-sm" aria-hidden="true"></i>
                                            </a>
                                            <a href="{{ route('portal.projects.gantt', $project) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400" title="{{ __('Gantt') }}">
                                                <span class="sr-only">{{ __('Gantt') }}</span>
                                                <i class="fa-solid fa-chart-gantt text-sm" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-600 dark:text-slate-400">
                                        @if ($q !== '' || ($status ?? '') !== '')
                                            {{ __('portal_projects_no_results') }}
                                        @else
                                            {{ __('No projects yet.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($projects->hasPages())
                    <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">{{ $projects->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
