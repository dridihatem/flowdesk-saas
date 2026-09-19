<?php

namespace App\AI\Nova\Tools\Client;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;

class UpdateClientTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'clients.update';
    }

    public function description(): string
    {
        return 'Update a client in the current company.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'client_id' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
            ],
            'required' => ['client_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_clients');
        /** @var Client $client */
        $client = $this->findInCompany(Client::class, (string) ($arguments['client_id'] ?? ''), $context);
        $data = Validator::make($arguments, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
        ])->validate();

        $client->fill(collect($data)->only(['name', 'email', 'phone'])->all());
        $client->save();

        return ['id' => $client->id, 'name' => $client->name, 'email' => $client->email];
    }
}
