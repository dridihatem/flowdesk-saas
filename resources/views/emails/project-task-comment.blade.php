<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1e293b;">
    <p>{{ __('Hello,') }}</p>

    @if ($notifyClient)
        <p>{{ __(':company replied to a comment on task « :task » in project « :project ».', [
            'company' => $company->name,
            'task' => $task->title,
            'project' => $project->title,
        ]) }}</p>
    @else
        <p>{{ __(':author left a comment on task « :task » in project « :project ».', [
            'author' => $comment->user?->name ?? __('Client'),
            'task' => $task->title,
            'project' => $project->title,
        ]) }}</p>
    @endif

    <blockquote style="margin: 1rem 0; padding: 0.75rem 1rem; border-left: 3px solid #6366f1; background: #f8fafc; color: #334155;">
        {{ $comment->body }}
    </blockquote>

    <p>
        <a href="{{ $actionUrl }}" style="display: inline-block; padding: 0.6rem 1.2rem; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            {{ $notifyClient ? __('View project in portal') : __('Open project tasks') }}
        </a>
    </p>

    <p style="font-size: 0.875rem; color: #64748b;">{{ __('If the button does not work, copy this link:') }}<br><span style="word-break: break-all;">{{ $actionUrl }}</span></p>
</body>
</html>
