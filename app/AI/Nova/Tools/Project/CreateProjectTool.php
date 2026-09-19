<?php

namespace App\AI\Nova\Tools\Project;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class CreateProjectTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'projects.create';
    }

    public function description(): string
    {
        return 'Create a project in the current company.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'client_id' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_projects');
        $data = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ])->validate();

        if (! empty($data['client_id'])) {
            $this->findInCompany(Client::class, $data['client_id'], $context);
        }

        $project = Project::query()->withoutGlobalScopes()->create([
            'company_id' => $context->companyId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => ProjectStatus::Pending->value,
            'client_id' => $data['client_id'] ?? null,
        ]);

        return ['id' => $project->id, 'name' => $project->name];
    }
}
