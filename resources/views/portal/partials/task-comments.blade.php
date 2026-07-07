@props(['project', 'task'])

<div class="mt-4 border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Comments') }}</p>
    @if ($task->comments->isNotEmpty())
        <ul class="mt-3 space-y-3">
            @foreach ($task->comments as $comment)
                <li @class([
                    'rounded-lg px-3 py-2',
                    'bg-indigo-50/80 dark:bg-indigo-950/30' => $comment->is_client,
                    'bg-emerald-50/70 dark:bg-emerald-950/20' => ! $comment->is_client,
                ])>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="font-medium text-slate-700 dark:text-slate-300">
                            {{ $comment->user?->name ?? __('Client') }}
                            @if ($comment->is_client)
                                <span class="ms-1 rounded bg-indigo-500/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-indigo-700 dark:text-indigo-300">{{ __('Client') }}</span>
                            @else
                                <span class="ms-1 rounded bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-800 dark:text-emerald-200">{{ __('task_comment_team_badge') }}</span>
                            @endif
                        </span>
                        <time datetime="{{ $comment->created_at?->toIso8601String() }}">{{ $comment->created_at?->format('Y-m-d H:i') }}</time>
                    </div>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{{ $comment->body }}</p>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('portal_no_task_comments') }}</p>
    @endif
    <form method="POST" action="{{ route('portal.projects.tasks.comments.store', [$project, $task]) }}" class="mt-3">
        @csrf
        <label class="sr-only" for="comment_{{ $task->id }}">{{ __('Add a comment') }}</label>
        <textarea
            id="comment_{{ $task->id }}"
            name="body"
            rows="2"
            required
            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            placeholder="{{ __('portal_task_comment_placeholder') }}"
        >{{ old('body') }}</textarea>
        <button type="submit" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">
            <i class="fa-solid fa-paper-plane text-[10px]" aria-hidden="true"></i>
            {{ __('Post comment') }}
        </button>
    </form>
</div>
