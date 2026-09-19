<?php

namespace App\AI\Nova\Tools\Invoice;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Invoice;

class GetInvoiceTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'invoices.get'; }
    public function description(): string { return 'Get an invoice by ID.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['invoice_id' => ['type' => 'string']], 'required' => ['invoice_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        /** @var Invoice $invoice */
        $invoice = $this->findInCompany(Invoice::class, (string) ($arguments['invoice_id'] ?? ''), $context);

        return [
            'id' => $invoice->id,
            'reference' => $invoice->reference ?? $invoice->number ?? null,
            'client_id' => $invoice->client_id,
            'status' => $invoice->status?->value ?? $invoice->status,
            'amount' => (int) ($invoice->amount ?? 0),
            'due_date' => optional($invoice->due_date)?->toDateString(),
        ];
    }
}
