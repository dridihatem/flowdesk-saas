@php
    $currencyOptions = flowdesk_currency_select_options($workspaceMoneyCurrency);
    $aiWorkflowAvailable = $aiWorkflowAvailable ?? false;
    $aiWorkflowCreditCost = $aiWorkflowCreditCost ?? 0;
    $projectMoneyCurrency = strtoupper((string) ($project->company?->default_currency ?? $workspaceMoneyCurrency));
    $taskCount = $totalTasks;
    $canVault = auth()->user()?->can('workspace.access_vault') ?? false;
    $vaultCount = $canVault ? $project->files->where('is_vault', true)->count() : 0;
    $allowedShowTabs = $canVault ? ['details', 'pricing', 'tasks', 'vault'] : ['details', 'pricing', 'tasks'];
    $initialShowTab = in_array($t = (string) request('tab', ''), $allowedShowTabs, true) ? $t : 'details';
    if ($errors->isNotEmpty()) {
        if ($errors->has('title')) {
            $initialShowTab = 'tasks';
        } elseif ($errors->has('due_date') && ! $errors->has('title')) {
            $initialShowTab = 'pricing';
        }
    }
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex min-w-0 flex-col gap-1 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('View project') }}</h2>
                <p class="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">{{ __($project->title) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('projects.tasks.kanban', $project) }}">
                    <x-secondary-button type="button">{{ __('Task board') }}</x-secondary-button>
                </a>
                <a href="{{ route('projects.tasks.gantt', $project) }}">
                    <x-secondary-button type="button">{{ __('Gantt timeline') }}</x-secondary-button>
                </a>
                <x-flow.show-action-button :href="route('projects.edit', $project)" variant="edit">{{ __('Edit') }}</x-flow.show-action-button>
                @if ($project->client_id && ! auth()->user()->hasRole('business_provider'))
                    @if ($project->invoices_exists)
                        <x-secondary-button type="button" disabled title="{{ __('An invoice is already linked to this project.') }}">
                            {{ __('Invoice already created from project') }}
                        </x-secondary-button>
                    @else
                        <a href="{{ route('invoices.create', ['project' => $project->id]) }}">
                            <x-primary-button type="button">{{ __('Create invoice from project') }}</x-primary-button>
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div
        class="py-10"
        x-data="{
            tab: @js($initialShowTab),
            applyTab(t) {
                this.tab = t;
                const u = new URL(window.location.href);
                if (t === 'details') {
                    u.searchParams.delete('tab');
                } else {
                    u.searchParams.set('tab', t);
                }
                window.history.replaceState(null, '', u);
            },
        }"
        x-init="
            (() => {
                if (@json($errors->isNotEmpty() || request()->has('tab'))) {
                    return;
                }
                if (window.location.hash === '#tasks') {
                    tab = 'tasks';
                } else if (window.location.hash === '#pricing') {
                    tab = 'pricing';
                }
            })();
        "
    >
        <div class="mx-auto max-w-8xl w-full space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->has('ai'))
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first('ai') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-indigo-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Tasks') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $totalTasks }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __(':done completed', ['done' => $doneTasks]) }}</p>
                </div>
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-emerald-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Progress') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $progressPct }}%</p>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-700/80">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-cyan-500 transition-all duration-500 ease-out"
                            style="width: {{ $progressPct }}%"
                        ></div>
                    </div>
                </div>
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-amber-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Overdue deadlines') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums {{ $overdueCount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white' }}">{{ $overdueCount }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Tasks past end date (not done)') }}</p>
                </div>
                <div class="flow-panel relative overflow-hidden p-5">
                    <div class="absolute inset-y-0 end-0 w-1/3 bg-gradient-to-l from-violet-500/10 to-transparent pointer-events-none"></div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Billable total') }}</p>
                    <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ $billableTotalFormatted ?? __('Various / none') }}</p>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Additive billable task lines (own price)') }}</p>
                </div>
            </div>

            <div
                class="flow-panel border border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/40"
            >
                <div
                    class="flex flex-col gap-4 border-b border-slate-200/80 p-4 sm:p-5 dark:border-slate-700/60 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ __('project_show_subtitle') }}
                    </p>
                    <nav
                        class="inline-flex w-full max-w-2xl flex-col gap-1 rounded-2xl border border-slate-200/90 bg-slate-50/90 p-1.5 sm:w-auto sm:flex-row sm:items-center sm:justify-end dark:border-slate-600/50 dark:bg-slate-800/50"
                        aria-label="{{ __('Project sections') }}"
                    >
                        <button
                            type="button"
                            @click="applyTab('details')"
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
                            @click="applyTab('pricing')"
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
                            @click="applyTab('tasks')"
                            :class="tab === 'tasks'
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-white dark:ring-slate-600/80'
                                : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        >
                            <i class="fa-solid fa-list-check w-4 text-center opacity-80" aria-hidden="true"></i>
                            <span>{{ __('Tasks') }} @if($taskCount > 0)<span class="ms-0.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-md bg-slate-200/90 px-1 text-xs font-bold tabular-nums text-slate-700 dark:bg-slate-600/50 dark:text-slate-100">{{ $taskCount }}</span>@endif</span>
                        </button>
                        @if ($canVault)
                            <button
                                type="button"
                                @click="applyTab('vault')"
                                :class="tab === 'vault'
                                    ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-white dark:ring-slate-600/80'
                                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white'"
                                class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                            >
                                <i class="fa-solid fa-vault w-4 text-center text-amber-500 opacity-90" aria-hidden="true"></i>
                                <span>{{ __('project_vault_title') }} @if($vaultCount > 0)<span class="ms-0.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-md bg-amber-100 px-1 text-xs font-bold tabular-nums text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">{{ $vaultCount }}</span>@endif</span>
                            </button>
                        @endif
                    </nav>
                </div>

                <div class="p-4 sm:p-6 lg:p-8">
                    <div x-show="tab === 'details'">
                        @include('projects.partials.show-tab-details')
                    </div>
                    <div x-cloak x-show="tab === 'pricing'">
                        @include('projects.partials.show-tab-pricing')
                    </div>
                    <div x-cloak x-show="tab === 'tasks'">
                        @include('projects.partials.show-tab-tasks')
                    </div>
                    @if ($canVault)
                        <div x-cloak x-show="tab === 'vault'">
                            @include('projects.partials.vault')
                        </div>
                    @endif
                </div>
            </div>

            <div class="flow-panel p-6 sm:p-8">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Proposals & estimates') }}</h3>
                <ul class="mt-4 space-y-2">
                    @forelse ($project->proposals as $proposal)
                        <li>
                            <a href="{{ route('proposals.show', $proposal) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $proposal->name }}</a>
                            <span class="text-sm text-slate-500">— {{ flowdesk_format_minor((int) $proposal->amount, $proposal->currency) }} {{ $proposal->currency }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">{{ __('No proposals yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            <x-flow.show-action-button :href="route('projects.index')" variant="back" class="mt-2">{{ __('Back to projects') }}</x-flow.show-action-button>

            @include('projects.partials.file-preview-modal')
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const leaveMsg = @json(__('Leave or reload? Pause the work timer first — your session is still running.'));

                function fmtElapsed(totalSec) {
                    const s = Math.max(0, Math.floor(Number(totalSec) || 0));
                    const h = Math.floor(s / 3600);
                    const m = Math.floor((s % 3600) / 60);
                    const r = s % 60;
                    return h + ':' + String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
                }

                function liveSec(row) {
                    const acc = parseInt(row.dataset.accumulated || '0', 10) || 0;
                    if (row.dataset.running !== '1' || !row.dataset.startedAt) {
                        return acc;
                    }
                    const start = Date.parse(row.dataset.startedAt);
                    if (Number.isNaN(start)) {
                        return acc;
                    }
                    return acc + Math.floor((Date.now() - start) / 1000);
                }

                function applyRowState(row, t) {
                    row.dataset.running = t.running ? '1' : '0';
                    row.dataset.startedAt = t.started_at || '';
                    row.dataset.accumulated = String(t.accumulated_seconds ?? 0);
                    const startBtn = row.querySelector('.task-start');
                    const pauseBtn = row.querySelector('.task-pause');
                    const elapsedEl = row.querySelector('.task-elapsed');
                    if (startBtn && pauseBtn) {
                        if (t.running) {
                            startBtn.classList.add('hidden');
                            pauseBtn.classList.remove('hidden');
                        } else {
                            pauseBtn.classList.add('hidden');
                            startBtn.classList.remove('hidden');
                        }
                    }
                    if (elapsedEl) {
                        elapsedEl.textContent = fmtElapsed(t.elapsed_seconds ?? liveSec(row));
                    }
                }

                function syncFromPayload(rows, list) {
                    if (!Array.isArray(list)) {
                        return;
                    }
                    const byId = Object.fromEntries(list.map((x) => [x.id, x]));
                    rows.forEach((row) => {
                        const id = row.dataset.taskId;
                        if (id && byId[id]) {
                            applyRowState(row, byId[id]);
                        }
                    });
                }

                const rows = Array.from(document.querySelectorAll('.task-work-row'));
                rows.forEach((row) => {
                    const elapsedEl = row.querySelector('.task-elapsed');
                    if (elapsedEl) {
                        elapsedEl.textContent = fmtElapsed(liveSec(row));
                    }
                    const running = row.dataset.running === '1';
                    const startBtn = row.querySelector('.task-start');
                    const pauseBtn = row.querySelector('.task-pause');
                    if (startBtn && pauseBtn) {
                        if (running) {
                            startBtn.classList.add('hidden');
                            pauseBtn.classList.remove('hidden');
                        }
                    }
                });

                setInterval(() => {
                    rows.forEach((row) => {
                        const elapsedEl = row.querySelector('.task-elapsed');
                        if (elapsedEl && row.dataset.running === '1') {
                            elapsedEl.textContent = fmtElapsed(liveSec(row));
                        }
                    });
                }, 1000);

                async function postJson(url) {
                    const r = await fetch(url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({}),
                    });
                    if (!r.ok) {
                        throw new Error('request failed');
                    }
                    return r.json();
                }

                rows.forEach((row) => {
                    row.querySelector('.task-start')?.addEventListener('click', async () => {
                        try {
                            const data = await postJson(row.dataset.startUrl);
                            syncFromPayload(rows, data.project_tasks);
                        } catch (e) {
                            window.alert(@json(__('Could not start timer.')));
                        }
                    });
                    row.querySelector('.task-pause')?.addEventListener('click', async () => {
                        try {
                            const data = await postJson(row.dataset.pauseUrl);
                            syncFromPayload(rows, data.project_tasks);
                        } catch (e) {
                            window.alert(@json(__('Could not pause timer.')));
                        }
                    });
                });

                window.addEventListener('beforeunload', (e) => {
                    if (document.querySelector('.task-work-row[data-running="1"]')) {
                        e.preventDefault();
                        e.returnValue = leaveMsg;
                        return leaveMsg;
                    }
                });

                window.addEventListener('pagehide', () => {
                    const active = document.querySelector('.task-work-row[data-running="1"]');
                    if (!active || !active.dataset.pauseUrl) {
                        return;
                    }
                    fetch(active.dataset.pauseUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        keepalive: true,
                        body: JSON.stringify({}),
                    }).catch(() => {});
                });
            });
        </script>
    @endpush
</x-app-layout>
