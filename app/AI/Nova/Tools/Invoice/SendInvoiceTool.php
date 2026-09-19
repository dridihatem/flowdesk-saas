<?php

namespace App\AI\Nova\Tools\Invoice;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceMailService;

class SendInvoiceTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private InvoiceMailService $mailer) {}

    public function name(): string
    {
        return 'invoices.send';
    }

    public function description(): string
    {
        return 'Send an invoice email (HIGH risk unless company allows auto-send).';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'invoice_id' => ['type' => 'string'],
            ],
            'required' => ['invoice_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        /** @var Invoice $invoice */
        $invoice = $this->findInCompany(Invoice::class, (string) ($arguments['invoice_id'] ?? ''), $context);
        $this->mailer->send($invoice, $context->company);
        if ($invoice->status === InvoiceStatus::Draft) {
            $invoice->update(['status' => InvoiceStatus::Sent]);
        }

        return [
            'id' => $invoice->id,
            'status' => $invoice->fresh()->status?->value ?? $invoice->status,
            'sent' => true,
        ];
    }
}
