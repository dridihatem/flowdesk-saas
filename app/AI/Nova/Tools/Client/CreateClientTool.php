<?php

namespace App\AI\Nova\Tools\Client;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Client;
use App\Services\ClientCodeService;
use Illuminate\Support\Facades\Validator;

class CreateClientTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private ClientCodeService $codes) {}

    public function name(): string
    {
        return 'clients.create';
    }

    public function description(): string
    {
        return 'Create a client in the current company.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_clients');
        $data = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
        ])->validate();

        $client = Client::query()->withoutGlobalScopes()->create([
            'company_id' => $context->companyId,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
        $this->codes->assignIfMissing($client);

        return ['id' => $client->id, 'name' => $client->name, 'code' => $client->code];
    }
}
