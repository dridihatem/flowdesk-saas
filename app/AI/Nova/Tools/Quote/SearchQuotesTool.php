<?php

namespace App\AI\Nova\Tools\Quote;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Proposal;

class SearchQuotesTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'quotes.search'; }
    public function description(): string { return 'Search quotes/proposals in the current company.'; }
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
        $this->assertPermission($context, 'workspace.manage_invoices');
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));
        $query = Proposal::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->orderByDesc('created_at')
            ->limit($limit);
        if (! empty($arguments['client_id'])) {
            $query->where('client_id', (string) $arguments['client_id']);
        }
        $q = trim((string) ($arguments['query'] ?? ''));
        if ($q !== '') {
            $query->where(function ($b) use ($q): void {
                $b->where('name', 'like', '%'.$q.'%')->orWhere('reference', 'like', '%'.$q.'%');
            });
        }
        $rows = $query->get();

        return [
            'count' => $rows->count(),
            'quotes' => $rows->map(fn (Proposal $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'reference' => $p->reference ?? null,
                'status' => $p->status?->value ?? $p->status,
                'client_id' => $p->client_id,
            ])->all(),
        ];
    }
}
