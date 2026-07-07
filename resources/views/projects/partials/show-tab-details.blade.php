<div class="space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Status') }}</p>
                <x-flow.badge variant="primary" class="mt-1">{{ $project->status->label() }}</x-flow.badge>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Source') }}</p>
                <p class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $project->source->label() }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm({{ json_encode(__('Delete this project?')) }})">
            @csrf
            @method('DELETE')
            <x-secondary-button type="submit" class="!border-rose-300 !text-rose-700 dark:!border-rose-800 dark:!text-rose-300">{{ __('Delete project') }}</x-secondary-button>
        </form>
    </div>
    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Client') }}</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $project->client?->name ?? __('None') }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Provider') }}</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $project->provider?->name ?? __('None') }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ __('Created by') }}</dt>
            <dd class="mt-1 text-slate-900 dark:text-white">{{ $project->creator?->name ?? '—' }}</dd>
        </div>
    </dl>
    @if ($project->formSubmission && $project->formSubmission->form)
        <p class="text-sm text-slate-600 dark:text-slate-400">
            {{ __('Converted from lead form') }}:
            <a href="{{ route('forms.submissions.index', $project->formSubmission->form) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $project->formSubmission->form->name }}</a>
        </p>
    @endif
    <div class="rounded-xl border border-slate-200/70 bg-white/60 p-4 dark:border-slate-600/50 dark:bg-slate-900/40">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="text-xs font-semibold uppercase text-slate-500">{{ __('Description') }}</p>
            @if ($aiWorkflowAvailable)
                <form method="POST" action="{{ route('projects.ai.generate-workflow', $project) }}" class="inline" onsubmit="this.querySelector('button[type=submit]').disabled=true">
                    @csrf
                    <x-primary-button type="submit" class="!text-xs !normal-case">{{ __('Generate description & tasks') }} ({{ $aiWorkflowCreditCost }} {{ __('credits') }})</x-primary-button>
                </form>
            @endif
        </div>
        @if ($aiWorkflowAvailable)
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('ai_task_credit_hint', ['credits' => $aiWorkflowCreditCost]) }}</p>
        @endif
        @if ($project->description)
            <details class="group mt-3">
                <summary class="cursor-pointer list-none text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 [&::-webkit-details-marker]:hidden">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-book-open text-xs opacity-80" aria-hidden="true"></i>
                        {{ __('Read full description') }}
                    </span>
                </summary>
                <div class="flow-rich-text mt-3 border-t border-slate-200/70 pt-3 text-sm leading-relaxed text-slate-700 dark:border-slate-600/50 dark:text-slate-300">
                    {!! $project->description !!}
                </div>
            </details>
        @else
            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ __('No description yet. Add one when editing the project, or use Generate description & tasks to draft from the title and context.') }}</p>
        @endif
    </div>

    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-600/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                    <i class="fa-solid fa-users text-indigo-500" aria-hidden="true"></i>
                    {{ __('Team & assignments') }}
                </h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('Choose workspace members responsible for delivery.') }}</p>
            </div>
        </div>
        @if ($teamUsers->isEmpty())
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('No other users in this workspace yet.') }}</p>
        @else
            <form method="POST" action="{{ route('projects.team', $project) }}" class="mt-6">
                @csrf
                @method('PATCH')
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($teamUsers as $u)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200/80 bg-white/80 p-3 shadow-sm transition hover:border-indigo-200/80 dark:border-slate-600/60 dark:bg-slate-900/40 dark:hover:border-indigo-500/40">
                            <input type="checkbox" name="team_user_ids[]" value="{{ $u->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600" @checked($project->teamMembers->contains('id', $u->id)) />
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xs font-bold uppercase text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-200">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($u->name, 0, 2)) }}
                            </span>
                            <span class="min-w-0">
                                <span class="block font-medium text-slate-900 dark:text-white">{{ $u->name }}</span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $u->email }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-6">
                    <x-primary-button type="submit">{{ __('Save team') }}</x-primary-button>
                </div>
            </form>
        @endif
        @if ($project->teamMembers->isNotEmpty())
            <div class="mt-8 border-t border-slate-200/80 pt-6 dark:border-slate-600/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Currently assigned') }}</p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($project->teamMembers as $member)
                        <li class="rounded-full border border-slate-200/80 bg-slate-50 px-3 py-1 text-sm font-medium text-slate-800 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-100">
                            {{ $member->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @php
        $storageUsed = $projectFileStorage['used_bytes'] ?? 0;
        $storageMax = max(1, $projectFileStorage['max_bytes'] ?? 1);
        $storagePct = min(100, (int) round(100 * $storageUsed / $storageMax));
        $maxFileKb = (int) ($projectFileStorage['max_file_kb'] ?? 12288);
    @endphp
    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-600/50">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Project files & documents') }}</h3>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ __('Storage for this project: :used MB of :max MB allowed.', [
                'used' => number_format($storageUsed / 1024 / 1024, 1),
                'max' => number_format($storageMax / 1024 / 1024, 0),
            ]) }}
        </p>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200/90 dark:bg-slate-700/80">
            <div
                class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all"
                style="width: {{ $storagePct }}%"
            ></div>
        </div>
        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            {{ __('Categories: general documents, statement of work / specs, screenshots. Images show a thumbnail; click to preview the original (fitted to your screen).') }}
        </p>
        @php($regularFiles = $project->files->where('is_vault', false))
        @if ($regularFiles->isEmpty())
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ __('No project-level files yet. Add one below or when creating/editing the project.') }}</p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($regularFiles as $file)
                    <li class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200/80 bg-white/60 p-3 dark:border-slate-600/50 dark:bg-slate-900/40">
                        @if ($file->isImage())
                            <button
                                type="button"
                                class="shrink-0 overflow-hidden rounded-lg ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:ring-slate-600"
                                @click="$dispatch('flowdesk-file-preview', { src: @js($file->url()), title: @js($file->original_name) })"
                            >
                                @if ($file->thumbUrl())
                                    <img src="{{ $file->thumbUrl() }}" alt="" class="h-16 w-16 object-cover" loading="lazy" />
                                @else
                                    <img src="{{ $file->url() }}" alt="" class="h-16 w-16 object-cover" loading="lazy" />
                                @endif
                            </button>
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-2xl text-slate-500 dark:bg-slate-800 dark:text-slate-400" aria-hidden="true">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <a href="{{ $file->url() }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                {{ $file->original_name }}
                            </a>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $file->categoryEnum()->label() }}</span>
                                @if ($file->size)
                                    <span>{{ number_format($file->size / 1024, 1) }} KB</span>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('projects.files.destroy', [$project, $file]) }}" class="shrink-0" onsubmit="return confirm({{ json_encode(__('Remove this file?')) }})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-rose-600 hover:underline dark:text-rose-400">{{ __('Remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            @csrf
            <div class="min-w-[160px] flex-1">
                <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400" for="pf_cat">{{ __('File category') }}</label>
                <select id="pf_cat" name="category" class="flow-input-select block w-full text-sm" required>
                    @foreach (\App\Enums\ProjectFileCategory::cases() as $cat)
                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0 flex-1 sm:min-w-[200px]">
                <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400" for="pf_file">{{ __('File') }}</label>
                <input
                    id="pf_file"
                    type="file"
                    name="file"
                    required
                    class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-slate-700 dark:file:text-indigo-200"
                />
            </div>
            <x-secondary-button type="submit" class="!text-xs !normal-case">{{ __('Upload') }}</x-secondary-button>
        </form>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            {{ __('Max :mb MB per file. Types: PDF, Office, images, ZIP…', ['mb' => number_format($maxFileKb / 1024, 0)]) }}
        </p>
    </div>
</div>
