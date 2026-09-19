<?php

namespace App\AI\Nova\Tools\Project;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Project;

class SearchProjectsTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'projects.search'; }
    public function description(): string { return 'Search projects in the current company.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'query' => ['type' => 'string'],
            'client_id' => ['type' => 'string'],
            'limit' => ['type' => 'integer', 'default' => 10],
        ]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $q = trim((string) ($arguments['query'] ?? ''));
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));
        $query = Project::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->orderByDesc('updated_at')
            ->limit($limit);
        if (! empty($arguments['client_id'])) {
            $query->where('client_id', (string) $arguments['client_id']);
        }
        if ($q !== '') {
            $query->where(function ($b) use ($q): void {
                $b->where('name', 'like', '%'.$q.'%');
            });
        }
        $rows = $query->get();
        $mapped = $rows->map(fn (Project $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'status' => $p->status?->value ?? $p->status,
            'client_id' => $p->client_id,
        ])->all();

        return ['count' => count($mapped), 'projects' => $mapped, 'ambiguous' => count($mapped) > 1];
    }
}
