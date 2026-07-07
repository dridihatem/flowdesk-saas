@php
    use App\Enums\InvoiceStatus;

    $st = $invoice->status;
    $badgeVariant = match ($st) {
        InvoiceStatus::Paid => 'success',
        InvoiceStatus::Overdue => 'danger',
        InvoiceStatus::Cancelled => 'slate',
        InvoiceStatus::Draft => 'slate',
        InvoiceStatus::Sent => 'primary',
    };
@endphp
<div class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/[0.06]">
    <div class="flex items-start justify-between gap-3 border-b border-slate-200/80 bg-slate-50/90 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-800/40">
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Invoice') }}</p>
            <p class="truncate font-mono text-sm font-bold text-slate-900 dark:text-white">{{ $invoice->number ?? '—' }}</p>
        </div>
        <x-flow.badge :variant="$badgeVariant" class="shrink-0 text-[10px]">{{ $st->label() }}</x-flow.badge>
    </div>
    <div class="flex-1 space-y-4 overflow-y-auto p-4 text-sm">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Client') }}</p>
            <p class="mt-0.5 font-medium text-slate-900 dark:text-white">{{ $invoice->client?->name ?? '—' }}</p>
        </div>
        @if ($invoice->due_date)
            <div class="flex justify-between gap-2 text-xs">
                <span class="text-slate-500 dark:text-slate-400">{{ __('Due date') }}</span>
                <span class="font-medium tabular-nums text-slate-900 dark:text-white">{{ $invoice->due_date->format('Y-m-d') }}</span>
            </div>
        @endif
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Lines') }}</p>
            <ul class="mt-2 space-y-2">
                @foreach ($invoice->items->take(8) as $item)
                    <li class="flex justify-between gap-2 text-xs">
                        <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-200">{{ $item->description }}</span>
                        <span class="shrink-0 tabular-nums text-slate-600 dark:text-slate-300">{{ flowdesk_format_minor((int) $item->total_amount, flowdesk_invoice_currency($invoice)) }}</span>
                    </li>
                @endforeach
                @if ($invoice->items->count() > 8)
                    <li class="text-[11px] text-slate-500 dark:text-slate-400">+ {{ __('more lines…') }}</li>
                @endif
            </ul>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700/80 dark:bg-slate-800/40">
            <div class="flex justify-between gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                <span>{{ __('Total') }}</span>
                <span class="tabular-nums">{{ flowdesk_format_minor((int) $invoice->amount, flowdesk_invoice_currency($invoice)) }} {{ flowdesk_invoice_currency($invoice) }}</span>
            </div>
        </div>
    </div>
    <div class="flex flex-wrap gap-2 border-t border-slate-200/80 bg-slate-50/50 p-3 dark:border-slate-700/80 dark:bg-slate-800/30">
        <a
            href="{{ route('invoices.show', $invoice) }}"
            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500"
        >
            <i class="fa-solid fa-up-right-from-square text-[10px]" aria-hidden="true"></i>
            {{ __('Open') }}
        </a>
        <a
            href="{{ route('invoices.pdf', $invoice) }}"
            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            title="{{ __('PDF') }}"
        >
            <i class="fa-regular fa-file-pdf text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
        </a>
    </div>
</div>
