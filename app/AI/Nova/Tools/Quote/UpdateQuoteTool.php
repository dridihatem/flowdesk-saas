<?php

namespace App\AI\Nova\Tools\Quote;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Proposal;
use Illuminate\Support\Facades\Validator;

class UpdateQuoteTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'quotes.update'; }
    public function description(): string { return 'Update a quote/proposal.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'quote_id' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'status' => ['type' => 'string'],
        ], 'required' => ['quote_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        /** @var Proposal $quote */
        $quote = $this->findInCompany(Proposal::class, (string) ($arguments['quote_id'] ?? ''), $context);
        $data = Validator::make($arguments, [
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:32'],
        ])->validate();
        $quote->fill(collect($data)->only(['name', 'status'])->all());
        $quote->save();

        return ['id' => $quote->id, 'name' => $quote->name, 'status' => $quote->status?->value ?? $quote->status];
    }
}
