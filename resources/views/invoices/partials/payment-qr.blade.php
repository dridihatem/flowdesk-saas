@props([
    'paymentQr' => null,
    'compact' => false,
])

@if (is_array($paymentQr) && ($paymentQr['data_uri'] ?? null))
    <div @class([
        'flex flex-col items-center text-center',
        'mt-4 border-t border-indigo-200/60 pt-4 dark:border-indigo-800/50' => ! $compact,
        'rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/40' => $compact,
    ])>
        <img
            src="{{ $paymentQr['data_uri'] }}"
            alt="{{ __('invoice_pdf_scan_to_pay') }}"
            class="h-32 w-32 rounded-lg bg-white p-1 ring-1 ring-slate-200 dark:ring-slate-700"
        />
        <p class="mt-2 text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('invoice_pdf_scan_to_pay') }}</p>
        @if ($paymentQr['url'] ?? null)
            <a
                href="{{ $paymentQr['url'] }}"
                class="mt-1 max-w-full break-all text-[11px] text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                target="_blank"
                rel="noopener noreferrer"
            >{{ $paymentQr['url'] }}</a>
        @endif
    </div>
@endif
