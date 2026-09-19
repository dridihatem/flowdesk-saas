<?php

namespace App\AI\Nova\Tools\Invoice;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceReferenceService;
use Illuminate\Support\Facades\Validator;

class CreateInvoiceTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function __construct(private InvoiceReferenceService $references) {}

    public function name(): string
    {
        return 'invoices.create';
    }

    public function description(): string
    {
        return 'Create a draft invoice for a client.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'client_id' => ['type' => 'string'],
                'amount' => ['type' => 'integer', 'description' => 'Amount in minor units'],
                'notes' => ['type' => 'string'],
            ],
            'required' => ['client_id'],
        ];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        $data = Validator::make($arguments, [
            'client_id' => ['required', 'string'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ])->validate();
        $this->findInCompany(Client::class, $data['client_id'], $context);

        $payload = [
            'company_id' => $context->companyId,
            'client_id' => $data['client_id'],
            'status' => InvoiceStatus::Draft->value,
            'amount' => (int) ($data['amount'] ?? 0),
            'currency' => $context->currency,
            'due_date' => now()->addDays(14)->toDateString(),
        ];
        if (array_key_exists('notes', $data) && \Illuminate\Support\Facades\Schema::hasColumn('invoices', 'notes')) {
            $payload['notes'] = $data['notes'];
        }
        $invoice = Invoice::query()->withoutGlobalScopes()->create($payload);
        $this->references->assignNextNumber($invoice, $context->company);

        return [
            'id' => $invoice->id,
            'reference' => $invoice->fresh()->reference ?? $invoice->number ?? null,
            'status' => InvoiceStatus::Draft->value,
        ];
    }
}
