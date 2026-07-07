@php
    $pc = strtoupper((string) ($project->company?->default_currency ?? 'USD'));
    $agreedMinor = $project->clientAgreedPriceMinor();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Project') }}</p>
                <h2 class="mt-0.5 font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ $project->title }}</h2>
            </div>
            <a href="{{ route('portal.projects.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to projects') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first() }}</div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                <div class="border-b border-slate-200/80 bg-gradient-to-br from-indigo-50/90 via-white to-white px-6 py-5 dark:border-slate-700/80 dark:from-indigo-950/40 dark:via-slate-900/50 dark:to-slate-900/40">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($project->status)
                                    <x-flow.badge :variant="$project->status->badgeVariant()">{{ $project->status->label() }}</x-flow.badge>
                                @endif
                                @if ($project->final_deadline)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        <i class="fa-regular fa-calendar text-[10px]" aria-hidden="true"></i>
                                        {{ $project->final_deadline->format('Y-m-d') }}
                                    </span>
                                @endif
                            </div>
                            @if ($project->provider)
                                <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                                    <i class="fa-solid fa-user-tie me-1 opacity-70" aria-hidden="true"></i>
                                    {{ $project->provider->name }}
                                </p>
                            @endif
                            @if ($totalTasks > 0)
                                <div class="mt-4 max-w-md">
                                    <div class="flex items-center justify-between gap-2 text-sm">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('Progress') }}</span>
                                        <span class="tabular-nums text-slate-500">{{ $doneCount }}/{{ $totalTasks }} · {{ $progressPct }}%</span>
                                    </div>
                                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500" style="width: {{ $progressPct }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('portal.projects.kanban', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200/80 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400">
                                <i class="fa-solid fa-table-columns text-xs" aria-hidden="true"></i>
                                {{ __('Task board') }}
                            </a>
                            <a href="{{ route('portal.projects.gantt', $project) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200/80 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400">
                                <i class="fa-solid fa-chart-gantt text-xs" aria-hidden="true"></i>
                                {{ __('Gantt timeline') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4">
                    @include('portal.partials.project-nav', ['project' => $project, 'active' => 'overview'])
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2 space-y-6">
                    @if ($project->description)
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Description') }}</h3>
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ \Illuminate\Support\Str::of(strip_tags($project->description))->trim() }}</p>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                        <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Tasks') }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('portal_tasks_comment_hint') }}</p>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($project->tasks as $task)
                                <div class="px-6 py-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                            <div class="mt-1 flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
                                                @if ($task->starts_on)
                                                    <span class="tabular-nums">{{ __('Start') }}: {{ $task->starts_on->format('Y-m-d') }}</span>
                                                @endif
                                                @if ($task->ends_on)
                                                    <span class="tabular-nums {{ $task->isOverdue() ? 'font-semibold text-amber-700 dark:text-amber-300' : '' }}">{{ __('Deadline') }}: {{ $task->ends_on->format('Y-m-d') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <x-flow.badge :variant="$task->status?->badgeVariant() ?? 'neutral'">{{ $task->status?->label() ?? $task->status?->value }}</x-flow.badge>
                                    </div>
                                    @if ($task->description)
                                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ $task->description }}</p>
                                    @endif
                                    @include('portal.partials.task-comments', ['project' => $project, 'task' => $task])
                                </div>
                            @empty
                                <p class="px-6 py-10 text-center text-sm text-slate-600 dark:text-slate-400">{{ __('No tasks published yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('At a glance') }}</h3>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Status') }}</dt>
                                <dd class="font-medium text-slate-900 dark:text-white">{{ $project->status?->label() ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Provider') }}</dt>
                                <dd class="text-end font-medium text-slate-900 dark:text-white">{{ $project->provider?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Deadline') }}</dt>
                                <dd class="font-medium tabular-nums text-slate-900 dark:text-white">{{ $project->final_deadline?->format('Y-m-d') ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Tasks') }}</dt>
                                <dd class="font-medium tabular-nums text-slate-900 dark:text-white">{{ $totalTasks }}</dd>
                            </div>
                        </dl>
                    </div>

                    @if ($agreedMinor !== null && (int) $agreedMinor > 0)
                        <div class="rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/80 to-white p-6 shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-slate-900/50">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Agreed project price') }}</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $agreedMinor, $pc) }} {{ $pc }}</p>
                            @if ($project->isClientPriceConfirmed())
                                <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-800 dark:text-emerald-200">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    {{ __('You confirmed this price on :date', ['date' => $project->client_price_confirmed_at->timezone(config('app.timezone'))->format('Y-m-d')]) }}
                                </p>
                            @else
                                <form method="POST" action="{{ route('portal.projects.confirm-price', $project) }}" class="mt-4">
                                    @csrf
                                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Confirm agreed price help') }}</p>
                                    <x-primary-button type="submit" class="mt-3 w-full justify-center !normal-case">{{ __('Confirm price') }}</x-primary-button>
                                </form>
                            @endif
                        </div>
                    @endif

                    @if ($project->isClientPriceConfirmed() && $project->installments->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Installment schedule') }}</h3>
                            <ul class="mt-4 space-y-3">
                                @foreach ($project->installments as $inst)
                                    <li class="rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-800/40">
                                        <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $inst->amount_minor, $pc) }} {{ $pc }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Due :date', ['date' => $inst->due_date->format('Y-m-d')]) }} · {{ $inst->payment_method->label() }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <a href="{{ route('chat.index') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200/90 bg-white p-4 text-sm font-medium text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-700/80 dark:bg-slate-900/50 dark:text-slate-200 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400">
                            <i class="fa-solid fa-comments" aria-hidden="true"></i>
                        </span>
                        {{ __('Contact your team') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
