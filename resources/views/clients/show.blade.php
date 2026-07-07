@php
    $stats = $payload['stats'];
    $currency = strtoupper((string) ($company->default_currency ?? 'USD'));
    $totalPaidFormatted = flowdesk_format_minor((int) $stats['total_paid_minor'], $currency).' '.$currency;
    $address = is_array($client->address) ? $client->address : [];
    $activeMeeting = collect($payload['meetings'])->firstWhere('id', $activeMeetingId)
        ?? $payload['meetings']->first();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Client follow-up') }}</p>
                <h2 class="mt-0.5 truncate text-xl font-semibold text-slate-900 dark:text-white">{{ $client->name }}</h2>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    @if ($client->code)
                        <span class="font-mono text-indigo-700 dark:text-indigo-300">{{ $client->code }}</span>
                    @endif
                    @if ($sourceCase)
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{{ $sourceCase->label() }}</span>
                    @endif
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusCase->badgeClass() }}">{{ $statusCase->label() }}</span>
                    @if ($client->email)
                        <a href="mailto:{{ $client->email }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">{{ $client->email }}</a>
                    @endif
                    @if ($client->phone)
                        <span class="flowdesk-ltr-num tabular-nums">{{ $client->phone }}</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('invoices.create', ['client' => $client->id]) }}">
                <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-xs" aria-hidden="true"></i>
                        {{ __('New invoice') }}
                    </x-secondary-button>
                </a>
                <a href="{{ route('proposals.create', ['client' => $client->id]) }}">
                    <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-xs" aria-hidden="true"></i>
                        {{ __('New quote') }}
                    </x-secondary-button>
                </a>
                <a href="{{ $payload['chatUrl'] }}">
                    <x-secondary-button type="button" class="!normal-case inline-flex items-center gap-2">
                        <i class="fa-regular fa-comments text-xs" aria-hidden="true"></i>
                        {{ __('Messages') }}
                    </x-secondary-button>
                </a>
                @if ($mutable)
                    <x-flow.show-action-button :href="route('clients.edit', $client)" variant="edit">{{ __('Edit') }}</x-flow.show-action-button>
                @endif
                <x-flow.show-action-button :href="route('clients.index')" variant="back">{{ __('Back') }}</x-flow.show-action-button>
            </div>
        </div>
    </x-slot>

    <div
        class="py-10"
        x-data="{ tab: @js($activeTab) }"
        x-init="
            $watch('tab', (value) => {
                const u = new URL(window.location.href);
                if (value === 'overview') {
                    u.searchParams.delete('tab');
                } else {
                    u.searchParams.set('tab', value);
                }
                window.history.replaceState(null, '', u);
            });
        "
    >
        <div class="mx-auto max-w-8xl w-full space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flow-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Invoices') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $stats['invoices_count'] }}</p>
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ __(':count unpaid', ['count' => $stats['unpaid_invoices']]) }}</p>
                </div>
                <div class="flow-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Payments received') }}</p>
                    <p class="mt-1 text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $totalPaidFormatted }}</p>
                </div>
                <div class="flow-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Quotes') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $stats['proposals_count'] }}</p>
                </div>
                <div class="flow-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Projects') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900 dark:text-white">{{ $stats['projects_count'] }}</p>
                </div>
            </div>

            <div class="flow-panel border border-slate-200/80 bg-white/90 p-2 dark:border-slate-700/60 dark:bg-slate-900/40">
                <nav class="flex flex-wrap gap-1" aria-label="{{ __('Client sections') }}">
                    @foreach ([
                        'overview' => __('Overview'),
                        'notes' => __('Notes'),
                        'timeline' => __('Timeline'),
                        'tasks' => __('Tasks'),
                        'invoices' => __('Invoices'),
                        'proposals' => __('Quotes'),
                        'payments' => __('Payments'),
                        'meetings' => __('Meetings'),
                        'reminders' => __('Reminders'),
                        'feedback' => __('Feedback'),
                        'messages' => __('Messages'),
                    ] as $key => $label)
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="tab === @js($key)
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                            @click="tab = @js($key)"
                        >{{ $label }}</button>
                    @endforeach
                </nav>
            </div>

            {{-- Overview --}}
            <div x-show="tab === 'overview'" x-cloak class="grid gap-6 lg:grid-cols-3">
                <div class="flow-panel space-y-4 p-6 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Contact & source') }}</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('client_status_label') }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">{{ $statusCase->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('client_source_label') }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-900 dark:text-white">{{ $sourceCase?->label() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Address') }}</dt>
                            <dd class="mt-0.5 text-slate-900 dark:text-white">
                                @if (! empty($address))
                                    {{ implode(', ', array_filter([$address['line1'] ?? null, $address['city'] ?? null, $address['country'] ?? null])) }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Portal account') }}</dt>
                            <dd class="mt-0.5 text-slate-900 dark:text-white">{{ $client->user_id ? __('Active') : __('Not created') }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Created') }}</dt>
                            <dd class="mt-0.5 text-slate-900 dark:text-white">{{ $client->created_at?->format('Y-m-d') }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="flow-panel p-6 lg:col-span-2">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Upcoming calendar') }}</h3>
                    <ul class="mt-4 divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse (array_slice($payload['calendarEvents'], 0, 8) as $event)
                            <li class="flex items-start justify-between gap-3 py-3 text-sm">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $event['title'] }}</p>
                                    <p class="text-slate-500 dark:text-slate-400">{{ $event['date'] }}@if (! empty($event['subtitle'])) · {{ $event['subtitle'] }} @endif</p>
                                </div>
                                @if (! empty($event['url']))
                                    <a href="{{ $event['url'] }}" class="shrink-0 text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Open') }}</a>
                                @endif
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('No upcoming events.') }}</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('calendar.index') }}" class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Open full calendar') }}</a>
                </div>
            </div>

            {{-- Notes --}}
            <div
                x-show="tab === 'notes'"
                x-cloak
                class="flow-panel p-6"
                x-data="{
                    authorKind: @js(old('author_kind', 'team')),
                    noteType: @js(old('note_type', 'general')),
                    hasPortal: @js((bool) $client->user_id),
                }"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('client_notes_heading') }}</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('client_notes_intro') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('clients.notes.store', $client) }}" class="mt-5 grid gap-4 border-b border-slate-200 pb-6 dark:border-slate-700 lg:grid-cols-2">
                    @csrf
                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="note_author_kind" :value="__('client_note_author_label')" />
                                <select id="note_author_kind" name="author_kind" x-model="authorKind" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    @foreach (\App\Enums\ClientNoteAuthorKind::cases() as $kind)
                                        <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="authorKind === 'provider'" x-cloak>
                                <x-input-label for="note_provider_id" :value="__('Provider')" />
                                <select id="note_provider_id" name="provider_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">{{ __('client_provider_feedback_select') }}</option>
                                    @foreach ($payload['providers'] as $provider)
                                        <option value="{{ $provider->id }}" @selected(old('provider_id') === $provider->id)>{{ $provider->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="note_type" :value="__('client_note_type_label')" />
                                <select id="note_type" name="note_type" x-model="noteType" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    @foreach (\App\Enums\ClientNoteType::cases() as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="noted_on" :value="__('Date')" />
                                <x-text-input id="noted_on" name="noted_on" type="date" class="mt-1 block w-full" required :value="old('noted_on', now()->toDateString())" />
                            </div>
                        </div>
                        <div x-show="['meeting', 'reminder', 'call'].includes(noteType)" x-cloak>
                            <x-input-label for="note_start_time" :value="__('Time')" />
                            <x-text-input id="note_start_time" name="start_time" type="time" class="mt-1 block w-full" :value="old('start_time')" />
                        </div>
                        <div x-show="noteType === 'meeting'" x-cloak>
                            <x-input-label for="note_meeting_url" :value="__('client_meeting_link_label')" />
                            <x-text-input id="note_meeting_url" name="meeting_url" type="url" class="mt-1 block w-full" placeholder="https://meet.google.com/..." :value="old('meeting_url')" />
                        </div>
                        <div x-show="['meeting', 'email', 'follow_up'].includes(noteType)" x-cloak>
                            <x-input-label for="note_title" :value="__('Title')" />
                            <x-text-input id="note_title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" />
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="note_body" :value="__('Notes')" />
                            <textarea id="note_body" name="body" rows="6" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required placeholder="{{ __('client_note_body_placeholder') }}">{{ old('body') }}</textarea>
                        </div>
                        <label class="flex items-start gap-2 text-sm text-slate-700 dark:text-slate-300" x-show="hasPortal" x-cloak>
                            <input type="checkbox" name="visible_to_client" value="1" @checked(old('visible_to_client')) class="mt-0.5 rounded border-slate-300 text-indigo-600 dark:border-slate-600" />
                            <span>{{ __('client_note_visible_to_client') }}</span>
                        </label>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-show="!hasPortal" x-cloak>{{ __('client_note_portal_required') }}</p>
                        <x-primary-button type="submit">{{ __('client_note_add') }}</x-primary-button>
                    </div>
                </form>

                <ul class="mt-6 space-y-3">
                    @forelse ($payload['notes'] as $note)
                        <li class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/80">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{{ $note->note_type?->label() }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $note->authorLabel($company->name) }}</span>
                                <time class="text-slate-500 dark:text-slate-400" datetime="{{ $note->noted_on->toDateString() }}">
                                    {{ $note->noted_on->format('Y-m-d') }}@if ($note->start_time) · {{ \Illuminate\Support\Str::substr((string) $note->start_time, 0, 5) }}@endif
                                </time>
                                @if ($note->visible_to_client)
                                    <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">{{ __('client_note_visible_badge') }}</span>
                                @endif
                            </div>
                            @if ($note->title)
                                <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">{{ $note->title }}</p>
                            @endif
                            <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $note->body }}</p>
                            @if ($note->meeting_url)
                                <a href="{{ $note->meeting_url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('client_meeting_join_button') }}</a>
                            @endif
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('client_notes_empty') }}</li>
                    @endforelse
                </ul>
            </div>

            {{-- Timeline --}}
            <div x-show="tab === 'timeline'" x-cloak class="flow-panel p-6">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('client_timeline_heading') }}</h3>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('client_timeline_intro') }}</p>
                <ol class="relative mt-6 space-y-0 border-s border-slate-200 ps-6 dark:border-slate-700">
                    @forelse ($payload['timeline'] as $entry)
                        @php
                            $icon = match ($entry['type']) {
                                'client_created' => 'fa-user-plus',
                                'proposal' => 'fa-file-lines',
                                'invoice' => 'fa-file-invoice',
                                'payment' => 'fa-money-bill-wave',
                                'meeting' => 'fa-video',
                                'reminder' => 'fa-bell',
                                'feedback_team', 'feedback_provider' => 'fa-star',
                                'task' => 'fa-list-check',
                                'task_comment' => 'fa-comment',
                                'inquiry' => 'fa-inbox',
                                default => 'fa-circle',
                            };
                            $at = \Illuminate\Support\Carbon::parse($entry['at']);
                        @endphp
                        <li class="relative pb-8 last:pb-0">
                            <span class="absolute -start-[2.5rem] flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-white text-xs text-indigo-600 dark:border-slate-600 dark:bg-slate-900 dark:text-indigo-400">
                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if (! empty($entry['url']))
                                        <a href="{{ $entry['url'] }}" class="text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">{{ $entry['title'] }}</a>
                                    @else
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $entry['title'] }}</p>
                                    @endif
                                    <time class="text-xs text-slate-500 dark:text-slate-400" datetime="{{ $at->toIso8601String() }}">{{ $at->format('Y-m-d H:i') }}</time>
                                </div>
                                @if (! empty($entry['meta']))
                                    <p class="mt-0.5 text-xs font-medium text-slate-600 dark:text-slate-300">{{ $entry['meta'] }}</p>
                                @endif
                                @if (! empty($entry['body']))
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $entry['body'] }}</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('client_timeline_empty') }}</li>
                    @endforelse
                </ol>
            </div>

            {{-- Tasks --}}
            <div x-show="tab === 'tasks'" x-cloak class="space-y-6">
                <div class="flow-panel p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Add task') }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('client_tasks_add_intro') }}</p>
                    @if ($payload['projects']->isEmpty())
                        <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                            {{ __('client_tasks_no_projects') }}
                            <a href="{{ route('projects.create', ['client' => $client->id]) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Create project') }}</a>
                        </p>
                    @else
                        <form method="POST" action="{{ route('clients.tasks.store', $client) }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @csrf
                            <div class="sm:col-span-2 lg:col-span-3">
                                <x-input-label for="client_task_title" :value="__('Task title')" />
                                <x-text-input id="client_task_title" name="title" type="text" class="mt-1 block w-full" required :value="old('title')" />
                                <x-input-error class="mt-2" :messages="$errors->get('title')" />
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <x-input-label for="client_task_description" :value="__('Description')" />
                                <textarea id="client_task_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('description') }}</textarea>
                            </div>
                            <div>
                                <x-input-label for="client_task_project" :value="__('client_task_project_label')" />
                                <select id="client_task_project" name="project_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                                    <option value="">{{ __('Select an option') }}</option>
                                    @foreach ($payload['projects'] as $project)
                                        <option value="{{ $project->id }}" @selected(old('project_id') === $project->id)>{{ $project->title }}</option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('project_id')" />
                            </div>
                            <div>
                                <x-input-label for="client_task_status" :value="__('Column')" />
                                <select id="client_task_status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    @foreach (\App\Enums\TaskStatus::kanbanOrder() as $st)
                                        <option value="{{ $st->value }}" @selected(old('status', 'todo') === $st->value)>{{ $st->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="client_task_ends_on" :value="__('Deadline')" />
                                <x-text-input id="client_task_ends_on" name="ends_on" type="date" class="mt-1 block w-full" :value="old('ends_on')" />
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <x-primary-button type="submit">{{ __('Create task') }}</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>

                @forelse ($payload['tasks'] as $task)
                    <div class="flow-panel p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $task->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $task->project?->title ?? '—' }}
                                    · {{ $task->status->label() }}
                                    @if ($task->ends_on)
                                        · {{ __('Due :date', ['date' => $task->ends_on->format('Y-m-d')]) }}
                                    @endif
                                </p>
                            </div>
                            @if ($task->project)
                                <a href="{{ route('projects.show', [$task->project, 'tab' => 'tasks']) }}" class="shrink-0 text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View project') }}</a>
                            @endif
                        </div>
                        @if ($task->comments->isNotEmpty())
                            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-700">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Comments') }}</p>
                                <ul class="mt-2 space-y-3">
                                    @foreach ($task->comments as $comment)
                                        <li class="rounded-lg bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800/60">
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ $comment->user?->name ?? ($comment->is_client ? __('Client') : __('Team')) }}</span>
                                                <span>{{ $comment->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="mt-1 text-slate-800 dark:text-slate-100">{{ $comment->body }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('client_task_no_comments') }}</p>
                        @endif
                    </div>
                @empty
                    <div class="flow-panel p-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('client_tasks_empty') }}</div>
                @endforelse
            </div>

            {{-- Invoices --}}
            <div x-show="tab === 'invoices'" x-cloak class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Number') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Due date') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($payload['invoices'] as $invoice)
                            @php $invCurrency = flowdesk_invoice_currency($invoice); @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $invoice->number ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $invoice->status->label() }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums">{{ flowdesk_format_minor((int) $invoice->amount, $invCurrency) }} {{ $invCurrency }}</td>
                                <td class="px-4 py-3 text-sm">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No invoices for this client.') }}</td></tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            {{-- Proposals --}}
            <div x-show="tab === 'proposals'" x-cloak class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Number') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($payload['proposals'] as $proposal)
                            @php $propCurrency = strtoupper((string) $proposal->currency); @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $proposal->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $proposal->number ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $proposal->status->label() }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums">{{ flowdesk_format_minor((int) $proposal->amount, $propCurrency) }} {{ $propCurrency }}</td>
                                <td class="px-4 py-3 text-end">
                                    <a href="{{ route('proposals.show', $proposal) }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No quotes for this client.') }}</td></tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            {{-- Payments --}}
            <div x-show="tab === 'payments'" x-cloak class="flow-panel overflow-hidden p-0">
                <x-flow.table>
                    <thead class="bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-start">{{ __('Invoice') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Method') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Paid at') }}</th>
                            <th class="px-4 py-3 text-start">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($payload['payments'] as $payment)
                            @php $payCurrency = strtoupper((string) ($payment->invoice?->currency ?? $currency)); @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $payment->invoice?->number ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm tabular-nums">{{ flowdesk_format_minor((int) $payment->amount, $payCurrency) }} {{ $payCurrency }}</td>
                                <td class="px-4 py-3 text-sm">{{ $payment->payment_method?->label() ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $payment->status->label() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">{{ __('No payments recorded for this client.') }}</td></tr>
                        @endforelse
                    </tbody>
                </x-flow.table>
            </div>

            {{-- Meetings --}}
            <div x-show="tab === 'meetings'" x-cloak class="grid gap-6 xl:grid-cols-2">
                <div class="flow-panel space-y-4 p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Schedule Google Meet') }}</h3>
                    @if (! $payload['googleConnected'])
                        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100">{{ __('client_meeting_google_required') }}</p>
                    @endif
                    <form method="POST" action="{{ route('clients.meetings.store', $client) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="meeting_title" :value="__('Title')" />
                            <x-text-input id="meeting_title" name="title" type="text" class="mt-1 block w-full" required :value="old('title', __('client_meeting_default_title', ['name' => $client->name]))" />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="meeting_date" :value="__('Date')" />
                                <x-text-input id="meeting_date" name="date" type="date" class="mt-1 block w-full" required :value="old('date')" />
                            </div>
                            <div>
                                <x-input-label for="meeting_time" :value="__('Time')" />
                                <x-text-input id="meeting_time" name="start_time" type="time" class="mt-1 block w-full" :value="old('start_time')" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="meeting_description" :value="__('Description')" />
                            <textarea id="meeting_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('description') }}</textarea>
                        </div>
                        <input type="hidden" name="meeting_link_type" value="google_meet" />
                        <div class="flex flex-wrap gap-4 text-sm">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="sync_google" value="1" class="rounded border-slate-300 text-indigo-600" @checked($payload['googleConnected']) />
                                <span>{{ __('Add to Google Calendar') }}</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="send_invite" value="1" class="rounded border-slate-300 text-indigo-600" @checked((bool) $client->email) />
                                <span>{{ __('Email invite to client') }}</span>
                            </label>
                        </div>
                        <x-primary-button type="submit">{{ __('Schedule meeting') }}</x-primary-button>
                    </form>

                    <div class="border-t border-slate-200 pt-4 dark:border-slate-700">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Past & upcoming meetings') }}</h4>
                        <ul class="mt-3 space-y-2">
                            @forelse ($payload['meetings'] as $meeting)
                                <li>
                                    <a
                                        href="{{ route('clients.show', [$client, 'tab' => 'meetings', 'meeting' => $meeting['id']]) }}"
                                        class="block rounded-lg border px-3 py-2 text-sm transition {{ (string) $activeMeetingId === (string) $meeting['id'] ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-500/50 dark:bg-indigo-950/30' : 'border-slate-200 hover:border-indigo-200 dark:border-slate-700 dark:hover:border-indigo-500/40' }}"
                                    >
                                        <span class="font-medium text-slate-900 dark:text-white">{{ $meeting['title'] }}</span>
                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $meeting['date'] }}@if ($meeting['start_time']) {{ \Illuminate\Support\Str::substr((string) $meeting['start_time'], 0, 5) }}@endif</span>
                                    </a>
                                </li>
                            @empty
                                <li class="text-sm text-slate-500">{{ __('No meetings yet.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="space-y-6">
                    @if ($activeMeeting && ! empty($activeMeeting['meeting_url']))
                        <div class="flow-panel overflow-hidden p-0">
                            <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Google Meet') }}</p>
                                <a href="{{ $activeMeeting['meeting_url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Open in new tab') }}</a>
                            </div>
                            <div class="relative aspect-video bg-slate-950">
                                <iframe
                                    src="{{ $activeMeeting['meeting_url'] }}"
                                    class="h-full w-full border-0"
                                    allow="camera; microphone; fullscreen; display-capture"
                                    title="{{ __('Google Meet') }}"
                                ></iframe>
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/90 to-transparent px-4 py-3 text-center text-[11px] text-slate-300">
                                    {{ __('client_meet_iframe_hint') }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @php $eventModel = $activeMeeting ? \App\Models\WorkspaceCalendarEvent::query()->find($activeMeeting['id']) : null; @endphp

                    @if ($activeMeeting && $eventModel)
                        <div
                            class="flow-panel space-y-4 p-6"
                            x-data="{
                                busy: false,
                                error: '',
                                async generateSummary() {
                                    if (this.busy) {
                                        return;
                                    }

                                    this.busy = true;
                                    this.error = '';

                                    try {
                                        const res = await fetch(@js(route('clients.meetings.summary.generate', [$client, $eventModel])), {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': @js(csrf_token()),
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json',
                                            },
                                            body: JSON.stringify({
                                                notes: this.$refs.summaryField?.value || '',
                                            }),
                                        });

                                        const data = await res.json().catch(() => ({}));
                                        if (!res.ok) {
                                            throw new Error(data.message || @js(__('Something went wrong.')));
                                        }

                                        const text = String(data.suggestion || '').trim();
                                        if (!text) {
                                            throw new Error(@js(__('Empty AI response.')));
                                        }

                                        this.$refs.summaryField.value = text;
                                    } catch (e) {
                                        this.error = e?.message || @js(__('Something went wrong.'));
                                    } finally {
                                        this.busy = false;
                                    }
                                },
                            }"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $activeMeeting['title'] }}</h3>
                                    @if ($activeMeeting['meeting_url'])
                                        <p class="mt-1 break-all text-xs text-indigo-600 dark:text-indigo-400">{{ $activeMeeting['meeting_url'] }}</p>
                                    @endif
                                </div>
                                @if ($client->email && $eventModel)
                                    <form method="POST" action="{{ route('clients.meetings.invite', [$client, $eventModel]) }}">
                                        @csrf
                                        <x-secondary-button type="submit" class="!text-xs">{{ __('Send invite by email') }}</x-secondary-button>
                                    </form>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('clients.meetings.summary', [$client, $eventModel]) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <x-input-label for="meeting_summary" :value="__('Meeting summary')" />
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 disabled:opacity-60 dark:border-indigo-500/30 dark:bg-indigo-950/30 dark:text-indigo-300 dark:hover:bg-indigo-950/40"
                                        @click="generateSummary()"
                                        :disabled="busy"
                                    >
                                        <i class="fa-solid fa-wand-magic-sparkles text-[11px]" aria-hidden="true"></i>
                                        <span x-show="!busy">{{ __('Generate with AI') }}</span>
                                        <span x-show="busy" x-cloak>{{ __('Working…') }}</span>
                                    </button>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('client_meeting_ai_help') }}</p>
                                <textarea x-ref="summaryField" id="meeting_summary" name="meeting_summary" rows="8" class="block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" placeholder="{{ __('client_meeting_summary_placeholder') }}">{{ old('meeting_summary', $activeMeeting['meeting_summary']) }}</textarea>
                                <p x-show="error" x-cloak x-text="error" class="text-sm text-rose-600 dark:text-rose-400"></p>
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-primary-button type="submit">{{ __('Save summary') }}</x-primary-button>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('AI-generated content — review before sending to clients.') }}</span>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reminders --}}
            <div x-show="tab === 'reminders'" x-cloak class="grid gap-6 lg:grid-cols-2">
                <div class="flow-panel space-y-4 p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Add reminder') }}</h3>
                    <form method="POST" action="{{ route('clients.reminders.store', $client) }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="reminder_title" :value="__('Title')" />
                            <x-text-input id="reminder_title" name="title" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="reminder_date" :value="__('Date')" />
                                <x-text-input id="reminder_date" name="date" type="date" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="reminder_time" :value="__('Time')" />
                                <x-text-input id="reminder_time" name="start_time" type="time" class="mt-1 block w-full" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="reminder_description" :value="__('Notes')" />
                            <textarea id="reminder_description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"></textarea>
                        </div>
                        <x-primary-button type="submit">{{ __('Save reminder') }}</x-primary-button>
                    </form>
                </div>
                <div class="flow-panel p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Scheduled reminders') }}</h3>
                    <ul class="mt-4 divide-y divide-slate-200/80 dark:divide-slate-700/80">
                        @forelse ($payload['reminders'] as $reminder)
                            <li class="py-3 text-sm">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $reminder->title }}</p>
                                <p class="text-slate-500">{{ $reminder->starts_on->format('Y-m-d') }}@if ($reminder->start_time) · {{ \Illuminate\Support\Str::substr((string) $reminder->start_time, 0, 5) }}@endif</p>
                                @if ($reminder->description)
                                    <p class="mt-1 text-slate-600 dark:text-slate-300">{{ $reminder->description }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-500">{{ __('No reminders yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Feedback --}}
            <div x-show="tab === 'feedback'" x-cloak class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-6">
                    <div class="flow-panel space-y-4 p-6">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Add feedback') }}</h3>
                        <form method="POST" action="{{ route('clients.feedbacks.store', $client) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="kind" value="team" />
                            <div>
                                <x-input-label for="feedback_rating" :value="__('Rating')" />
                                <select id="feedback_rating" name="rating" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    <option value="">{{ __('Optional') }}</option>
                                    @foreach (range(5, 1) as $star)
                                        <option value="{{ $star }}">{{ $star }} / 5</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="feedback_body" :value="__('Feedback')" />
                                <textarea id="feedback_body" name="body" rows="5" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required placeholder="{{ __('client_feedback_placeholder') }}"></textarea>
                            </div>
                            <x-primary-button type="submit">{{ __('Save feedback') }}</x-primary-button>
                        </form>
                    </div>

                    <div class="flow-panel space-y-4 p-6">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('client_provider_feedback_heading') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('client_provider_feedback_help') }}</p>
                        <form method="POST" action="{{ route('clients.feedbacks.store', $client) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="kind" value="provider" />
                            <div>
                                <x-input-label for="provider_feedback_provider" :value="__('Provider')" />
                                <select id="provider_feedback_provider" name="provider_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                                    <option value="">{{ __('client_provider_feedback_select') }}</option>
                                    @foreach ($payload['providers'] as $provider)
                                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="provider_feedback_body" :value="__('Feedback')" />
                                <textarea id="provider_feedback_body" name="body" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required placeholder="{{ __('client_provider_feedback_placeholder') }}"></textarea>
                            </div>
                            <x-primary-button type="submit">{{ __('Save feedback') }}</x-primary-button>
                        </form>
                    </div>
                </div>
                <div class="flow-panel p-6">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Feedback history') }}</h3>
                    <ul class="mt-4 space-y-4">
                        @forelse ($payload['feedbacks'] as $feedback)
                            <li class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/80">
                                <div class="flex items-center justify-between gap-2 text-xs text-slate-500">
                                    <span>
                                        @if ($feedback->kind?->value === 'provider')
                                            {{ $feedback->provider?->name ?? __('Provider') }}
                                            <span class="ms-1 rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-violet-800 dark:bg-violet-950/50 dark:text-violet-200">{{ __('client_feedback_kind_provider') }}</span>
                                        @else
                                            {{ $feedback->author?->name ?? __('Team') }}
                                        @endif
                                    </span>
                                    <span>{{ $feedback->created_at?->format('Y-m-d H:i') }}</span>
                                </div>
                                @if ($feedback->rating)
                                    <p class="mt-1 text-amber-500" aria-label="{{ __('Rating') }}: {{ $feedback->rating }}/5">
                                        @for ($i = 0; $i < $feedback->rating; $i++)★@endfor
                                    </p>
                                @endif
                                <p class="mt-2 text-sm text-slate-800 dark:text-slate-100">{{ $feedback->body }}</p>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-500">{{ __('No feedback yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            {{-- Messages --}}
            <div x-show="tab === 'messages'" x-cloak class="flow-panel p-8 text-center">
                <i class="fa-regular fa-comments text-4xl text-indigo-500" aria-hidden="true"></i>
                <p class="mt-4 text-sm text-slate-600 dark:text-slate-300">{{ __('client_messages_intro') }}</p>
                <a href="{{ $payload['chatUrl'] }}" class="mt-6 inline-flex">
                    <x-primary-button type="button">{{ __('Open conversation') }}</x-primary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
