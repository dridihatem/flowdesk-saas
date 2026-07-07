@props(['project', 'task'])

<div class="mt-4 border-t border-slate-200/70 pt-4 dark:border-slate-600/50">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Comments') }}</p>
    @if ($task->comments->isNotEmpty())
        <ul class="mt-3 space-y-3">
            @foreach ($task->comments as $comment)
                <li @class([
                    'rounded-lg px-3 py-2',
                    'bg-indigo-50/80 dark:bg-indigo-950/30' => $comment->is_client,
                    'bg-slate-50/90 dark:bg-slate-900/60' => ! $comment->is_client,
                ])>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="font-medium text-slate-700 dark:text-slate-300">
                            {{ $comment->user?->name ?? ($comment->is_client ? __('Client') : __('Team')) }}
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
    <form method="POST" action="{{ route('projects.tasks.comments.store', [$project, $task]) }}" class="mt-3">
        @csrf
        <label class="sr-only" for="team_comment_{{ $task->id }}">{{ __('Reply to client') }}</label>
        <textarea
            id="team_comment_{{ $task->id }}"
            name="body"
            rows="2"
            required
            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            placeholder="{{ __('task_comment_reply_placeholder') }}"
        >{{ old('body') }}</textarea>
        <button type="submit" class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">
            <i class="fa-solid fa-reply text-[10px]" aria-hidden="true"></i>
            {{ __('Reply') }}
        </button>
    </form>
</div>
