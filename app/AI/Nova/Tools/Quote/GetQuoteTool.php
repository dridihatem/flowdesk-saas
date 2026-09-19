<?php

namespace App\AI\Nova\Tools\Quote;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Proposal;

class GetQuoteTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'quotes.get'; }
    public function description(): string { return 'Get a quote/proposal by ID.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['quote_id' => ['type' => 'string']], 'required' => ['quote_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        /** @var Proposal $quote */
        $quote = $this->findInCompany(Proposal::class, (string) ($arguments['quote_id'] ?? ''), $context);

        return [
            'id' => $quote->id,
            'name' => $quote->name,
            'reference' => $quote->reference ?? null,
            'status' => $quote->status?->value ?? $quote->status,
            'client_id' => $quote->client_id,
        ];
    }
}
