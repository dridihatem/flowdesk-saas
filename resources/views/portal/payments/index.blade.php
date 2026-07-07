<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Client portal') }}</p>
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Invoices & payments') }}</h2>
            </div>
            <a href="{{ route('portal.dashboard') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">{{ __('Back to portal') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="border-b border-slate-200/80 bg-slate-50/80 px-5 py-4 dark:border-slate-700/80 dark:bg-slate-800/40">
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ __('Signed in as :name.', ['name' => $client->name]) }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed text-start text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/80 text-start text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/80 dark:text-slate-400">
                                <th class="px-5 py-3 text-start">{{ __('Invoice') }}</th>
                                <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Total') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Paid') }}</th>
                                <th class="px-5 py-3 text-end">{{ __('Balance') }}</th>
                                <th class="px-5 py-3 text-start"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($invoices as $invoice)
                                @php
                                    $paid = $invoice->completedPaymentsTotalMinor();
                                    $balance = max(0, (int) $invoice->amount - $paid);
                                    $ic = flowdesk_invoice_currency($invoice);
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="px-5 py-4 font-mono font-medium text-slate-900 dark:text-white text-start">{{ $invoice->number ?? $invoice->id }}</td>
                                    <td class="px-5 py-4 text-start">
                                        <x-flow.badge variant="primary">{{ $invoice->status?->label() ?? $invoice->status?->value }}</x-flow.badge>
                                    </td>
                                    <td class="px-5 py-4 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $invoice->amount, $ic) }}</span></td>
                                    <td class="px-5 py-4 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ flowdesk_format_minor((int) $paid, $ic) }}</span></td>
                                    <td class="px-5 py-4 text-end font-medium {{ $balance > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}"><span class="flowdesk-ltr-num tabular-nums font-medium">
                                        {{ flowdesk_format_minor($balance, $ic) }}
                                    </span></td>
                                    <td class="px-5 py-4 text-end">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            <a
                                                href="{{ route('portal.invoices.show', $invoice) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                                                title="{{ __('View') }}"
                                            >
                                                <span class="sr-only">{{ __('View') }}</span>
                                                <i class="fa-regular fa-eye text-sm" aria-hidden="true"></i>
                                            </a>
                                            @if ($balance > 0)
                                                <a
                                                    href="{{ route('portal.invoices.show', $invoice) }}"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200/80 bg-emerald-50/80 text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:border-emerald-700/50"
                                                    title="{{ __('View & pay') }}"
                                                >
                                                    <span class="sr-only">{{ __('View & pay') }}</span>
                                                    <i class="fa-solid fa-credit-card text-sm" aria-hidden="true"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-slate-600 dark:text-slate-400">{{ __('No invoices yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($invoices->hasPages())
                    <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/80">{{ $invoices->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
