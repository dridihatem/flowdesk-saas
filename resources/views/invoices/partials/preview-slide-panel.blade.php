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
<div class="flex h-full min-h-0 flex-col">
    <div class="flex-1 space-y-4 overflow-y-auto p-4 text-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Invoice reference column') }}</p>
                <p class="mt-0.5 font-mono text-lg font-bold tracking-tight text-slate-900 dark:text-white">{{ $invoice->number ?? '—' }}</p>
            </div>
            <x-flow.badge :variant="$badgeVariant" class="shrink-0 text-[10px]">{{ $st->label() }}</x-flow.badge>
        </div>
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
                @foreach ($invoice->items->take(12) as $item)
                    <li class="flex justify-between gap-2 text-xs">
                        <span class="min-w-0 flex-1 text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::limit($item->description, 80) }}</span>
                        <span class="shrink-0 tabular-nums text-slate-600 dark:text-slate-300">{{ flowdesk_format_minor((int) $item->total_amount, flowdesk_invoice_currency($invoice)) }}</span>
                    </li>
                @endforeach
                @if ($invoice->items->count() > 12)
                    <li class="text-[11px] text-slate-500 dark:text-slate-400">+ {{ __('more lines…') }}</li>
                @endif
            </ul>
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 dark:border-slate-700/80 dark:bg-slate-800/40">
            <div class="flex justify-between gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                <span>{{ __('Total (inc. VAT)') }}</span>
                <span class="tabular-nums">{{ flowdesk_format_minor((int) $invoice->amount, flowdesk_invoice_currency($invoice)) }} {{ flowdesk_invoice_currency($invoice) }}</span>
            </div>
        </div>
    </div>
    <div class="shrink-0 space-y-2 border-t border-slate-200/80 bg-slate-50/90 p-4 dark:border-slate-700/80 dark:bg-slate-800/40">
        <a
            href="{{ route('invoices.show', $invoice) }}"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500"
        >
            <i class="fa-solid fa-file-invoice text-[11px]" aria-hidden="true"></i>
            {{ __('View full detail') }}
        </a>
        <div @class(['grid gap-2', 'grid-cols-2' => $canManageInvoices ?? false])>
            @if ($canManageInvoices ?? false)
                <a
                    href="{{ route('invoices.edit', $invoice) }}"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                >
                    <i class="fa-solid fa-pen-to-square text-[10px]" aria-hidden="true"></i>
                    {{ __('Edit') }}
                </a>
            @endif
            <a
                href="{{ route('invoices.pdf', $invoice) }}"
                target="_blank"
                rel="noopener noreferrer"
                @class([
                    'inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700',
                    'col-span-2' => ! ($canManageInvoices ?? false),
                ])
            >
                <i class="fa-regular fa-file-pdf text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
                {{ __('Print PDF') }}
            </a>
        </div>
        @if ($canManageInvoices ?? false)
            <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}" class="pt-1">
                @csrf
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    <i class="fa-regular fa-copy text-[11px]" aria-hidden="true"></i>
                    {{ __('Clone') }}
                </button>
            </form>
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="pt-1" onsubmit="return confirm({{ json_encode(__('Delete this invoice?')) }})">
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 shadow-sm transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-950/50"
                >
                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                    {{ __('Delete') }}
                </button>
            </form>
        @endif
    </div>
</div>
