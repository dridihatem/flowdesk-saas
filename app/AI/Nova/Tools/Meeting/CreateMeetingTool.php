<?php

namespace App\AI\Nova\Tools\Meeting;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\WorkspaceCalendarEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class CreateMeetingTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'meetings.create';
    }

    public function description(): string
    {
        return 'Create a calendar meeting/event.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'starts_on' => ['type' => 'string'],
                'ends_on' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['title', 'starts_on'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $data = Validator::make($arguments, [
            'title' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'description' => ['nullable', 'string'],
        ])->validate();

        $starts = Carbon::parse($data['starts_on'])->toDateString();
        $ends = isset($data['ends_on'])
            ? Carbon::parse($data['ends_on'])->toDateString()
            : $starts;

        $event = WorkspaceCalendarEvent::query()->withoutGlobalScopes()->create([
            'company_id' => $context->companyId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_on' => $starts,
            'ends_on' => $ends,
            'kind' => 'meeting',
            'created_by' => $context->userId,
        ]);

        return [
            'id' => $event->id,
            'title' => $event->title,
            'starts_on' => optional($event->starts_on)?->toDateString(),
        ];
    }
}
