<?php

namespace App\AI\Nova\Tools\Meeting;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\WorkspaceCalendarEvent;

class GetMeetingTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'meetings.get';
    }

    public function description(): string
    {
        return 'Get a meeting/event by ID.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'meeting_id' => ['type' => 'string'],
            ],
            'required' => ['meeting_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        /** @var WorkspaceCalendarEvent $event */
        $event = $this->findInCompany(WorkspaceCalendarEvent::class, (string) ($arguments['meeting_id'] ?? ''), $context);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'starts_on' => optional($event->starts_on)?->toDateString(),
            'ends_on' => optional($event->ends_on)?->toDateString(),
            'kind' => $event->kind,
            'meeting_url' => $event->meeting_url ?? null,
        ];
    }
}
