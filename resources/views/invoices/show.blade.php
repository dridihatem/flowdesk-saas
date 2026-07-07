@php
    use App\Enums\InvoiceStatus;
    use App\Enums\PaymentEntryKind;
    use App\Enums\PaymentStatus;
    use App\Enums\RemittanceMethod;

    $completedTotal = $invoice->completedPaymentsTotalMinor();
    $balanceMinor = max(0, $invoice->amount - $completedTotal);
    $ic = flowdesk_invoice_currency($invoice);
    $paySep = flowdesk_locale_amount_separators();
    $payScale = flowdesk_currency_minor_scale($ic);
    $payFd = flowdesk_currency_fraction_digits($ic);
    $paymentQuickPicks = collect(config('flowdesk.invoice_payment_quick_presets', []))
        ->map(function (array $row) use ($invoice, $balanceMinor, $ic): ?array {
            $pct = (float) ($row['percent'] ?? 0);
            if ($pct <= 0) {
                return null;
            }
            $raw = flowdesk_minor_percent_of_total((int) $invoice->amount, $pct);
            $suggested = min($balanceMinor, $raw);
            if ($suggested < 1) {
                return null;
            }

            return [
                'label' => __('invoice_payment_quick_pct', ['percent' => (int) round($pct)]),
                'amount_major' => flowdesk_major_amount_for_input($suggested, $ic),
                'amount_minor' => $suggested,
                'percent' => $pct,
            ];
        })
        ->filter()
        ->values()
        ->all();
    $paymentQr = flowdesk_invoice_payment_qr($invoice);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Invoice') }}</p>
                <h2 class="mt-0.5 font-mono text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $invoice->number ?? '—' }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('invoices.pdf', $invoice) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                >
                    <i class="fa-regular fa-file-pdf text-rose-600 dark:text-rose-400" aria-hidden="true"></i>
                    {{ __('PDF') }}
                </a>
                @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                    <form method="POST" action="{{ route('invoices.send', $invoice) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-900 shadow-sm transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-100 dark:hover:bg-indigo-900/40"
                        >
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            {{ __('Send') }}
                        </button>
                    </form>
                    <x-flow.show-action-button :href="route('invoices.edit', $invoice)" variant="edit">{{ __('Edit') }}</x-flow.show-action-button>
                    <form method="POST" action="{{ route('invoices.duplicate', $invoice) }}" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                            title="{{ __('Duplicate invoice') }}"
                        >
                            <i class="fa-regular fa-copy text-slate-500 dark:text-slate-400" aria-hidden="true"></i>
                            {{ __('Clone') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="inline" onsubmit="return confirm({{ json_encode(__('Delete this invoice?')) }})">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-700 shadow-sm transition hover:bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-950/50"
                        >
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                            {{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-8xl w-full space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/[0.06]">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700/80 dark:from-slate-800/40 dark:to-slate-900/40">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Bill to') }}</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ $invoice->client?->name ?? '—' }}</p>
                            @if ($invoice->client?->code)
                                <p class="mt-0.5 font-mono text-sm text-indigo-600 dark:text-indigo-400">{{ __('Client code') }}: {{ $invoice->client->code }}</p>
                            @endif
                            @if ($invoice->client?->email)
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $invoice->client->email }}</p>
                            @endif
                        </div>
                        <div class="text-end">
                            <x-flow.badge variant="primary" class="text-xs">{{ $invoice->status->label() }}</x-flow.badge>
                            @if ($invoice->due_date)
                                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('Due') }} <span class="font-medium tabular-nums text-slate-900 dark:text-white">{{ $invoice->due_date->format('Y-m-d') }}</span></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="grid gap-6 p-6 sm:grid-cols-2">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-slate-700/80">
                            <dt class="text-slate-500 dark:text-slate-400">{{ __('Subtotal (ex. VAT)') }}</dt>
                            <dd class="tabular-nums font-medium text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $invoice->subtotal_amount, $ic) }} {{ $ic }}</dd>
                        </div>
                        @if ($invoice->vat_amount > 0)
                            <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-slate-700/80">
                                <dt class="text-slate-500 dark:text-slate-400">{{ __('VAT') }}</dt>
                                <dd class="tabular-nums font-medium text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $invoice->vat_amount, $ic) }} {{ $ic }}</dd>
                            </div>
                        @endif
                        @if ($invoice->fiscal_stamp_amount > 0)
                            <div class="flex justify-between gap-4 border-b border-slate-100 pb-2 dark:border-slate-700/80">
                                <dt class="text-slate-500 dark:text-slate-400">{{ __('Fiscal stamp') }}</dt>
                                <dd class="tabular-nums font-medium text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $invoice->fiscal_stamp_amount, $ic) }} {{ $ic }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4 pt-1">
                            <dt class="text-base font-semibold text-slate-900 dark:text-white">{{ __('Total (inc. VAT)') }}</dt>
                            <dd class="text-lg font-bold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $invoice->amount, $ic) }} {{ $ic }}</dd>
                        </div>
                    </dl>
                    <div class="rounded-xl border border-indigo-200/60 bg-indigo-50/40 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-800 dark:text-indigo-200">{{ __('Settlement') }}</p>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600 dark:text-slate-400">{{ __('Advance paid') }}</dt>
                                <dd class="tabular-nums font-semibold text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $completedTotal, $ic) }} {{ $ic }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-indigo-200/60 pt-2 dark:border-indigo-800/50">
                                <dt class="font-medium text-slate-800 dark:text-slate-200">{{ __('Balance due') }}</dt>
                                <dd class="tabular-nums text-lg font-bold text-indigo-900 dark:text-indigo-100">{{ flowdesk_format_minor((int) $balanceMinor, $ic) }} {{ $ic }}</dd>
                            </div>
                        </dl>
                        @include('invoices.partials.payment-qr', ['paymentQr' => $paymentQr])
                    </div>
                </div>
                @if ($invoice->proposal)
                    <div class="border-t border-slate-200/80 px-6 py-3 text-sm dark:border-slate-700/80">
                        <span class="text-slate-500 dark:text-slate-400">{{ __('Proposal') }}:</span>
                        <a href="{{ route('proposals.show', $invoice->proposal) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $invoice->proposal->name }}</a>
                    </div>
                @endif
                @if ($invoice->customer_notes)
                    <div class="border-t border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                        <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Customer notes') }}</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300">{{ $invoice->customer_notes }}</p>
                    </div>
                @endif
                @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']) && $invoice->internal_notes)
                    <div class="border-t border-amber-200/80 bg-amber-50/40 px-6 py-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                        <p class="text-xs font-semibold uppercase text-amber-800 dark:text-amber-200">{{ __('Internal notes') }}</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-amber-950 dark:text-amber-100">{{ $invoice->internal_notes }}</p>
                    </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="border-b border-slate-200/80 bg-slate-50/90 px-6 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Line items') }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Amounts in :currency. Unit and line totals ex. VAT (HT); line TTC allocates document VAT by line share. Fiscal stamp is in the footer totals only.', ['currency' => $ic]) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full w-full table-fixed text-sm text-start">
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-white text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-700/80 dark:bg-slate-900/60 dark:text-slate-400">
                                <th class="w-[34%] px-5 py-3 text-start">{{ __('Description') }}</th>
                                <th class="w-[8%] px-5 py-3 text-end">{{ __('Qty') }}</th>
                                <th class="w-[16%] px-5 py-3 text-end">{{ __('Unit price (HT)') }}</th>
                                <th class="w-[16%] px-5 py-3 text-end">{{ __('Line total (HT)') }}</th>
                                <th class="w-[16%] px-5 py-3 text-end">{{ __('Line total (TTC)') }}</th>
                                <th class="w-[10%] px-5 py-3 text-center">{{ __('Currency') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach ($invoice->items as $row)
                                @php
                                    $lineHt = $row->total_amount;
                                    $lineTtc = $invoice->lineTotalTtcDisplayMinor($lineHt);
                                @endphp
                                <tr class="text-slate-800 dark:text-slate-100">
                                    <td class="px-5 py-3 text-start align-top">{{ $row->description }}</td>
                                    <td class="px-5 py-3 text-end align-top">
                                        <span class="flowdesk-ltr-num tabular-nums">{{ $row->quantity }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-end align-top">
                                        <span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $row->unit_amount, $ic) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-end align-top">
                                        <span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $lineHt, $ic) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-end align-top">
                                        <span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $lineTtc, $ic) }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-center text-xs font-semibold uppercase text-slate-500 align-top">
                                        <span class="flowdesk-ltr-num tabular-nums">{{ $ic }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']) && $invoice->status !== InvoiceStatus::Cancelled)
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40"
                    x-data="invoicePaymentQuickFill(@js([
                        'totalMinor' => (int) $invoice->amount,
                        'paidMinor' => (int) $completedTotal,
                        'balanceMinor' => (int) $balanceMinor,
                        'scale' => $payScale,
                        'fractionDigits' => $payFd,
                        'dec' => $paySep['decimal'],
                        'thou' => $paySep['thousands'],
                        'currency' => $ic,
                        'picks' => $paymentQuickPicks,
                    ]))"
                >
                    <div class="border-b border-slate-200/80 bg-slate-50/90 px-6 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Payments & balance') }}</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Record payments in the invoice currency. Completed payments reduce the balance due. Use quick amounts for common advance splits.') }}</p>
                    </div>
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-3 text-xs text-slate-600 dark:border-slate-700/60 dark:bg-slate-800/30 dark:text-slate-400">
                        <p class="font-medium text-slate-700 dark:text-slate-300">{{ __('Common schedules (reference)') }}</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            <li>{{ __('invoice_schedule_20_80') }}</li>
                            <li>{{ __('invoice_schedule_25_75') }}</li>
                            <li>{{ __('invoice_schedule_25_x4') }}</li>
                            <li>{{ __('invoice_schedule_100_end') }}</li>
                        </ul>
                    </div>
                    <div class="p-6">
                        @if ($invoice->payments->isNotEmpty())
                            @php $runningCompletedMinor = 0; @endphp
                            <ul class="mb-6 space-y-2">
                                @foreach ($invoice->payments as $p)
                                    @php
                                        if ($p->status === PaymentStatus::Completed) {
                                            $runningCompletedMinor += (int) $p->amount;
                                        }
                                        $stillDueAfter = max(0, (int) $invoice->amount - $runningCompletedMinor);
                                        $paidOn = $p->paid_at ?? $p->created_at;
                                    @endphp
                                    <li class="flex flex-col gap-3 rounded-xl border border-slate-200/80 px-4 py-3 dark:border-slate-700/80">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Payment') }} #{{ $loop->iteration }}</p>
                                                <span class="font-mono text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $p->amount, $p->currency) }} {{ $p->currency }}</span>
                                                <span class="ms-2 text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Payment date') }}: <span class="tabular-nums">{{ $paidOn?->format('Y-m-d') }}</span></span>
                                                @if ($p->created_at && $paidOn && ! $p->created_at->isSameDay($paidOn))
                                                    <span class="mt-0.5 block text-[11px] text-slate-500">{{ __('Logged in app at') }} {{ $p->created_at->format('Y-m-d H:i') }}</span>
                                                @endif
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    <span class="rounded-md bg-violet-500/15 px-2 py-0.5 text-[11px] font-medium text-violet-900 dark:text-violet-100">{{ $p->payment_kind->label() }}</span>
                                                    <span class="rounded-md bg-sky-500/15 px-2 py-0.5 text-[11px] font-medium text-sky-900 dark:text-sky-100">{{ $p->payment_method->label() }}</span>
                                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $p->status->label() }}</span>
                                                </div>
                                                @if ($p->receipt_path)
                                                    <a href="{{ route('payments.receipt', $p) }}" class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                                        <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                                        {{ __('Client receipt') }}
                                                    </a>
                                                @endif
                                                @if ($p->client_notes)
                                                    <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ $p->client_notes }}</p>
                                                @endif
                                                <dl class="mt-3 grid gap-1 text-[11px] text-slate-600 dark:text-slate-400 sm:grid-cols-2">
                                                    <div class="flex justify-between gap-2 border-t border-slate-100 pt-2 dark:border-slate-700/80">
                                                        <dt>{{ __('Paid so far (completed)') }}</dt>
                                                        <dd class="font-mono font-semibold tabular-nums text-slate-800 dark:text-slate-200">{{ flowdesk_format_minor($runningCompletedMinor, $ic) }} {{ $ic }}</dd>
                                                    </div>
                                                    <div class="flex justify-between gap-2 border-t border-slate-100 pt-2 dark:border-slate-700/80">
                                                        <dt>{{ __('Still due after this row') }}</dt>
                                                        <dd class="font-mono font-semibold tabular-nums text-indigo-800 dark:text-indigo-200">{{ flowdesk_format_minor($stillDueAfter, $ic) }} {{ $ic }}</dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>
                                        @if (auth()->user()->hasAnyRole(['company_admin', 'team_member']))
                                            <form method="POST" action="{{ route('invoices.payments.update', [$invoice, $p]) }}" class="flex flex-col gap-2 border-t border-slate-100 pt-3 dark:border-slate-700/80 sm:flex-row sm:flex-wrap sm:items-end">
                                                @csrf
                                                @method('PATCH')
                                                <div class="min-w-[9rem] flex-1">
                                                    <label class="mb-0.5 block text-[10px] font-medium uppercase text-slate-500">{{ __('Payment date') }}</label>
                                                    <input
                                                        type="date"
                                                        name="paid_at"
                                                        value="{{ old('paid_at', ($p->paid_at ?? $p->created_at)?->format('Y-m-d')) }}"
                                                        class="block w-full rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                                    />
                                                </div>
                                                <div class="min-w-[8rem] flex-1">
                                                    <label class="mb-0.5 block text-[10px] font-medium uppercase text-slate-500">{{ __('Status') }}</label>
                                                    <select name="status" class="block w-full rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                                        @foreach (PaymentStatus::cases() as $st)
                                                            <option value="{{ $st->value }}" @selected($p->status === $st)>{{ $st->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="min-w-[8rem] flex-1">
                                                    <label class="mb-0.5 block text-[10px] font-medium uppercase text-slate-500">{{ __('Payment type') }}</label>
                                                    <select name="payment_kind" class="block w-full rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                                        @foreach (PaymentEntryKind::cases() as $k)
                                                            <option value="{{ $k->value }}" @selected($p->payment_kind === $k)>{{ $k->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="min-w-[8rem] flex-1">
                                                    <label class="mb-0.5 block text-[10px] font-medium uppercase text-slate-500">{{ __('Payment method') }}</label>
                                                    <select name="payment_method" class="block w-full rounded-lg border-slate-300 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                                        @foreach (RemittanceMethod::cases() as $m)
                                                            <option value="{{ $m->value }}" @selected($p->payment_method === $m)>{{ $m->label() }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Update') }}</button>
                                            </form>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($balanceMinor > 0 && count($paymentQuickPicks) > 0)
                            <div class="mb-4">
                                <p class="text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('Quick amount (% of invoice total, max. balance due)') }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($paymentQuickPicks as $pick)
                                        <button
                                            type="button"
                                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-900 hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-100 dark:hover:bg-indigo-900/50"
                                            @click="applyQuickPick(@js($pick['amount_major']), {{ (int) $pick['amount_minor'] }}, @js($pick['percent']))"
                                        >
                                            {{ $pick['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            <div>
                                <x-input-label for="amount" :value="__('Amount (:currency)', ['currency' => $ic])" />
                                <x-text-input id="amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full" :placeholder="flowdesk_format_minor(0, $ic)" required @input="onAmountInput($event)" />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="balanceMinor > 0" x-cloak>
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('Balance due now') }}:</span>
                                    {{ flowdesk_format_minor((int) $balanceMinor, $ic) }} {{ $ic }}
                                    <span class="mx-1">·</span>
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ __('After this payment (preview)') }}:</span>
                                    <span class="tabular-nums text-indigo-700 dark:text-indigo-300" x-text="fmtDisplay(draftRemainingMinor)"></span>
                                    {{ $ic }}
                                </p>
                            </div>
                            <div>
                                <x-input-label for="paid_at" :value="__('Payment date')" />
                                <input
                                    id="paid_at"
                                    type="date"
                                    name="paid_at"
                                    value="{{ old('paid_at', now()->toDateString()) }}"
                                    class="flow-input mt-1 block w-full"
                                />
                            </div>
                            <div>
                                <x-input-label for="payment_status" :value="__('Status')" />
                                <select id="payment_status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    @foreach (PaymentStatus::cases() as $st)
                                        <option value="{{ $st->value }}" @selected($st === PaymentStatus::Completed)>{{ $st->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="payment_kind" :value="__('Payment type')" />
                                <select id="payment_kind" name="payment_kind" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    @foreach (PaymentEntryKind::cases() as $k)
                                        <option value="{{ $k->value }}" @selected($k === PaymentEntryKind::Standard)>{{ $k->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="payment_method" :value="__('Payment method')" />
                                <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                    @foreach (RemittanceMethod::cases() as $m)
                                        <option value="{{ $m->value }}" @selected($m === RemittanceMethod::BankTransfer)>{{ $m->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="notes" :value="__('Notes (optional)')" />
                                <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800"></textarea>
                            </div>
                            <div>
                                <x-primary-button type="submit">{{ __('Record payment') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <x-flow.show-action-button :href="route('invoices.index')" variant="back">{{ __('Back to invoices') }}</x-flow.show-action-button>
        </div>
    </div>
</x-app-layout>
