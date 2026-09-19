<?php

namespace App\AI\Nova\Tools\Report;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\Concerns\EnsuresTenantAccess;
use App\AI\Nova\Tools\NovaTool;

class InvoicesReportTool implements NovaTool
{
    use EnsuresTenantAccess;

    public function name(): string { return 'reports.invoices'; }
    public function description(): string { return 'Invoice status report including overdue.'; }
    public function schema(): array
    {
        return ['type' => 'object', 'properties' => ['filter' => ['type' => 'string']]];
    }

    public function execute(array $arguments, NovaContext $context): mixed
    {

        $base = \App\Models\Invoice::query()->withoutGlobalScopes()->where('company_id', $context->companyId);
        $filter = (string) ($arguments['filter'] ?? 'all');
        $overdue = (clone $base)->where('status', \App\Enums\InvoiceStatus::Overdue)->count();
        $sent = (clone $base)->where('status', \App\Enums\InvoiceStatus::Sent)->count();
        $paid = (clone $base)->where('status', \App\Enums\InvoiceStatus::Paid)->count();
        $list = (clone $base)
            ->when($filter === 'overdue', fn ($q) => $q->where('status', \App\Enums\InvoiceStatus::Overdue))
            ->orderByDesc('created_at')->limit(10)->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'number' => $i->number,
                'status' => $i->status?->value ?? $i->status,
                'amount' => (int) $i->amount,
                'client_id' => $i->client_id,
            ])->all();
        return compact('overdue', 'sent', 'paid', 'list') + ['filter' => $filter];

    }
}
