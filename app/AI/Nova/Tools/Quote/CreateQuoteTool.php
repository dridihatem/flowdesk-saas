<?php

namespace App\AI\Nova\Tools\Quote;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Client;
use App\Models\Proposal;
use App\Services\ProposalReferenceService;
use Illuminate\Support\Facades\Validator;

class CreateQuoteTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private ProposalReferenceService $references) {}

    public function name(): string
    {
        return 'quotes.create';
    }

    public function description(): string
    {
        return 'Create a draft quote/proposal.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'client_id' => ['type' => 'string'],
                'name' => ['type' => 'string'],
            ],
            'required' => ['client_id', 'name'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        $data = Validator::make($arguments, [
            'client_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ])->validate();
        $this->findInCompany(Client::class, $data['client_id'], $context);

        $quote = Proposal::query()->withoutGlobalScopes()->create([
            'company_id' => $context->companyId,
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'status' => 'draft',
            'amount' => 0,
            'currency' => $context->currency,
        ]);
        $this->references->assignNextNumber($quote, $context->company);

        return [
            'id' => $quote->id,
            'name' => $quote->name,
            'reference' => $quote->fresh()->reference ?? null,
        ];
    }
}
