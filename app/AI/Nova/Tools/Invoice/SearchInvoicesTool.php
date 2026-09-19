<?php

namespace App\AI\Nova\Tools\Invoice;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class SearchInvoicesTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'invoices.search'; }
    public function description(): string { return 'Search invoices; filter by client, status, or unpaid/overdue.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'client_id' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'limit' => ['type' => 'integer', 'default' => 10],
        ]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {
        $this->assertPermission($context, 'workspace.manage_invoices');
        $limit = min(25, max(1, (int) ($arguments['limit'] ?? 10)));
        $query = Invoice::query()->withoutGlobalScopes()
            ->where('company_id', $context->companyId)
            ->orderByDesc('created_at')
            ->limit($limit);

        if (! empty($arguments['client_id'])) {
            $query->where('client_id', (string) $arguments['client_id']);
        }

        $status = (string) ($arguments['status'] ?? '');
        if ($status === 'unpaid') {
            $query->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::Overdue->value, InvoiceStatus::Draft->value]);
        } elseif ($status === 'overdue') {
            $query->where('status', InvoiceStatus::Overdue->value);
        } elseif ($status !== '') {
            $query->where('status', $status);
        }

        $rows = $query->get();
        $total = 0;
        $items = $rows->map(function (Invoice $inv) use (&$total) {
            $amount = (int) ($inv->amount ?? 0);
            $total += $amount;

            return [
                'id' => $inv->id,
                'reference' => $inv->reference ?? $inv->number ?? null,
                'client_id' => $inv->client_id,
                'status' => $inv->status?->value ?? $inv->status,
                'amount' => $amount,
                'due_date' => optional($inv->due_date)?->toDateString(),
            ];
        })->all();

        return ['count' => count($items), 'outstanding_total' => $total, 'invoices' => $items];
    }
}
