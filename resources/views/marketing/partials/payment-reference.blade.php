@props([
    'reference',
    'copyId' => 'payment-reference-copy',
])

<div class="rounded-lg border border-amber-300/80 bg-white/80 px-4 py-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-amber-900/80">{{ __('marketing_checkout_payment_reference_label') }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <code id="{{ $copyId }}" class="text-lg font-bold tracking-wide text-amber-950">{{ $reference }}</code>
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-900 transition hover:bg-amber-200"
            data-copy-target="{{ $copyId }}"
            onclick="copyPaymentReference(this)"
        >
            <i class="fa-regular fa-copy" aria-hidden="true"></i>
            {{ __('Copy') }}
        </button>
    </div>
    <p class="mt-2 text-xs text-amber-800">{{ __('marketing_checkout_payment_reference_help') }}</p>
</div>

@once
    @push('scripts')
        <script>
            function copyPaymentReference(button) {
                const id = button.getAttribute('data-copy-target');
                const el = document.getElementById(id);
                if (!el) {
                    return;
                }
                const text = el.textContent?.trim() || '';
                if (!text) {
                    return;
                }
                navigator.clipboard?.writeText(text).then(() => {
                    const original = button.innerHTML;
                    button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> {{ __('Copied') }}';
                    setTimeout(() => {
                        button.innerHTML = original;
                    }, 2000);
                });
            }
        </script>
    @endpush
@endonce
