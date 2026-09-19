<?php

namespace App\AI\Nova\Tools\Project;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class UpdateProjectTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'projects.update'; }
    public function description(): string { return 'Update a project in the current company.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'project_id' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'status' => ['type' => 'string'],
        ], 'required' => ['project_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        /** @var Project $project */
        $project = $this->findInCompany(Project::class, (string) ($arguments['project_id'] ?? ''), $context);
        $data = Validator::make($arguments, [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:64'],
        ])->validate();
        $project->fill(collect($data)->only(['name', 'description', 'status'])->all());
        $project->save();

        return ['id' => $project->id, 'name' => $project->name ?? $project->title ?? null, 'status' => $project->status?->value ?? $project->status];
    }
}
