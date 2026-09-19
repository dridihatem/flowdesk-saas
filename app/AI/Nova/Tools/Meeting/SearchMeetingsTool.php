<?php

namespace App\AI\Nova\Tools\Meeting;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\WorkspaceCalendarEvent;

class SearchMeetingsTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'meetings.search';
    }

    public function description(): string
    {
        return 'Search workspace calendar meetings/events.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'default' => 10],
            ],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));
        $q = trim((string) ($arguments['query'] ?? ''));
        $query = WorkspaceCalendarEvent::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->orderBy('starts_on')
            ->limit($limit);
        if ($q !== '') {
            $query->where('title', 'like', '%'.$q.'%');
        }
        $rows = $query->get();

        return [
            'count' => $rows->count(),
            'meetings' => $rows->map(fn (WorkspaceCalendarEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'starts_on' => optional($e->starts_on)?->toDateString(),
                'ends_on' => optional($e->ends_on)?->toDateString(),
                'kind' => $e->kind,
            ])->all(),
        ];
    }
}
