<?php

namespace App\AI\Nova\Tools\Quote;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Proposal;
use App\Services\ProposalMailService;

class SendQuoteTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private ProposalMailService $mailer) {}

    public function name(): string
    {
        return 'quotes.send';
    }

    public function description(): string
    {
        return 'Send a quote email (HIGH risk).';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'quote_id' => ['type' => 'string'],
            ],
            'required' => ['quote_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        /** @var Proposal $quote */
        $quote = $this->findInCompany(Proposal::class, (string) ($arguments['quote_id'] ?? ''), $context);
        $this->mailer->send($quote, $context->company);

        return ['id' => $quote->id, 'sent' => true];
    }
}
