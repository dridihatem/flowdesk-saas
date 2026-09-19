<?php

namespace App\AI\Nova\Tools\Client;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Client;

class GetClientTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'clients.get';
    }

    public function description(): string
    {
        return 'Get a single client by ID within the current company.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'client_id' => ['type' => 'string'],
            ],
            'required' => ['client_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_clients');
        /** @var Client $client */
        $client = $this->findInCompany(Client::class, (string) ($arguments['client_id'] ?? ''), $context);

        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'code' => $client->code,
            'phone' => $client->phone,
            'status' => $client->status?->value ?? $client->status,
        ];
    }
}
