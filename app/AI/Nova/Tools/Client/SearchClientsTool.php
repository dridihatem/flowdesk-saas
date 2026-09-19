<?php

namespace App\AI\Nova\Tools\Client;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Client;

class SearchClientsTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string
    {
        return 'clients.search';
    }

    public function description(): string
    {
        return 'Search clients in the current company by name, email, or code.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string'],
                'limit' => ['type' => 'integer', 'default' => 10],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_clients');
        $q = trim((string) ($arguments['query'] ?? ''));
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));

        $query = Client::query()
            ->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->orderBy('name')
            ->limit($limit);

        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            });
        }

        $rows = $query->get(['id', 'name', 'email', 'code', 'status', 'phone']);

        return [
            'count' => $rows->count(),
            'clients' => $rows->map(fn (Client $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'code' => $c->code,
                'status' => $c->status?->value ?? $c->status,
                'phone' => $c->phone,
            ])->all(),
            'ambiguous' => $rows->count() > 1,
        ];
    }
}
