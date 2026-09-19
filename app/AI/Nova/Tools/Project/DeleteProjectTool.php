<?php

namespace App\AI\Nova\Tools\Project;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Project;

class DeleteProjectTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'projects.delete'; }
    public function description(): string { return 'Delete a project (HIGH risk — requires confirmation).'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['project_id' => ['type' => 'string']], 'required' => ['project_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var Project $project */
        $project = $this->findInCompany(Project::class, (string) ($arguments['project_id'] ?? ''), $context);
        $id = $project->id;
        $name = $project->name ?? $project->title ?? $id;
        $project->delete();

        return ['deleted' => true, 'id' => $id, 'name' => $name];
    }
}
