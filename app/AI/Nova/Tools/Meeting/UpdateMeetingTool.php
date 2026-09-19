<?php

namespace App\AI\Nova\Tools\Meeting;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Facades\Validator;

class UpdateMeetingTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'meetings.update';
    }

    public function description(): string
    {
        return 'Update a calendar meeting/event.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'meeting_id' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'starts_on' => ['type' => 'string'],
                'ends_on' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['meeting_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        /** @var WorkspaceCalendarEvent $event */
        $event = $this->findInCompany(WorkspaceCalendarEvent::class, (string) ($arguments['meeting_id'] ?? ''), $context);
        $data = Validator::make($arguments, [
            'title' => ['sometimes', 'string', 'max:255'],
            'starts_on' => ['sometimes', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ])->validate();
        $event->fill(collect($data)->only(['title', 'description', 'starts_on', 'ends_on'])->all());
        $event->save();

        return ['id' => $event->id, 'title' => $event->title];
    }
}
