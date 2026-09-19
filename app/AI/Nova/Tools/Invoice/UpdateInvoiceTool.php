<?php

namespace App\AI\Nova\Tools\Invoice;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Models\Invoice;
use Illuminate\Support\Facades\Validator;

class UpdateInvoiceTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'invoices.update'; }
    public function description(): string { return 'Update invoice notes or status (non-send).'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'invoice_id' => ['type' => 'string'],
            'notes' => ['type' => 'string'],
            'status' => ['type' => 'string'],
        ], 'required' => ['invoice_id']];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        /** @var Invoice $invoice */
        $invoice = $this->findInCompany(Invoice::class, (string) ($arguments['invoice_id'] ?? ''), $context);
        $data = Validator::make($arguments, [
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:32'],
        ])->validate();
        $invoice->fill(collect($data)->only(['notes', 'status'])->all());
        $invoice->save();

        return ['id' => $invoice->id, 'status' => $invoice->status?->value ?? $invoice->status];
    }
}
