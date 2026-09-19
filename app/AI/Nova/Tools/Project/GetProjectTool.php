<?php

namespace App\AI\Nova\Tools\Project;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Project;

class GetProjectTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'projects.get'; }
    public function description(): string { return 'Get a project by ID in the current company.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['project_id' => ['type' => 'string']], 'required' => ['project_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var Project $project */
        $project = $this->findInCompany(Project::class, (string) ($arguments['project_id'] ?? ''), $context);

        return [
            'id' => $project->id,
            'name' => $project->name ?? $project->title ?? null,
            'status' => $project->status?->value ?? $project->status,
            'client_id' => $project->client_id,
            'description' => $project->description,
        ];
    }
}
