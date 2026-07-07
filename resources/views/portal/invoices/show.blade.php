@php
    use App\Enums\InvoiceStatus;
    use App\Services\InvoicePaymentGatewayService;
    $ic = flowdesk_invoice_currency($invoice);
    $hasStripe = collect($paymentMethods)->contains(fn ($m) => $m['id'] === InvoicePaymentGatewayService::GATEWAY_STRIPE);
    $stripePk = $hasStripe ? ($paymentCreds['stripe_public_key'] ?? null) : null;
    $paymentQr = flowdesk_invoice_payment_qr($invoice);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Invoice') }}</p>
                <h2 class="mt-0.5 font-mono text-xl font-bold text-slate-900 dark:text-white">{{ $invoice->number ?? $invoice->id }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('portal.invoices.pdf', $invoice) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                    <i class="fa-regular fa-file-pdf text-rose-600" aria-hidden="true"></i>
                    {{ __('PDF') }}
                </a>
                <a href="{{ route('portal.payments.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300">{{ __('Back to invoices') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-12xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif
            @if ($errors->has('payment'))
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">{{ $errors->first('payment') }}</div>
            @endif
            @if ($paymentBanner === 'success')
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ __('portal_payment_success') }}</div>
            @elseif ($paymentBanner === 'pending')
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/50 dark:text-amber-100">{{ __('portal_payment_pending_confirmation') }}</div>
            @elseif ($paymentBanner === 'cancelled')
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/90 px-4 py-3 text-sm text-slate-700 dark:border-slate-700/80 dark:bg-slate-800/50 dark:text-slate-200">{{ __('portal_payment_cancelled') }}</div>
            @endif

            <div class="grid gap-6 xl:grid-cols-5">
                <div class="xl:col-span-3 space-y-6">
                    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                        <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50/90 to-white px-6 py-5 dark:border-slate-700/80 dark:from-slate-800/40 dark:to-slate-900/50">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <x-flow.badge variant="primary">{{ $invoice->status?->label() ?? $invoice->status?->value }}</x-flow.badge>
                                <div class="text-end">
                                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Total') }}</p>
                                    <p class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ flowdesk_format_minor((int) $invoice->amount, $ic) }} {{ $ic }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-4 px-6 py-5 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-800/30">
                                <p class="text-xs text-slate-500">{{ __('Due date') }}</p>
                                <p class="mt-1 font-semibold tabular-nums">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                <p class="text-xs text-emerald-700 dark:text-emerald-300">{{ __('Paid') }}</p>
                                <p class="mt-1 font-semibold tabular-nums text-emerald-800 dark:text-emerald-200">{{ flowdesk_format_minor((int) $completedTotal, $ic) }} {{ $ic }}</p>
                            </div>
                            <div class="rounded-xl border border-indigo-200/80 bg-indigo-50/50 px-4 py-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                                <p class="text-xs text-indigo-700 dark:text-indigo-300">{{ __('Balance due') }}</p>
                                <p class="mt-1 font-semibold tabular-nums text-indigo-800 dark:text-indigo-200">{{ flowdesk_format_minor((int) $balanceMinor, $ic) }} {{ $ic }}</p>
                            </div>
                        </div>

                        @if ($invoice->items->isNotEmpty())
                            <div class="border-t border-slate-200/80 px-6 py-5 dark:border-slate-700/80">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Line items') }}</h3>
                                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-700/80">
                                    <table class="min-w-full table-fixed text-start text-sm">
                                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase text-slate-500 dark:bg-slate-800/50">
                                            <tr>
                                                <th class="px-4 py-2 text-start">{{ __('Description') }}</th>
                                                <th class="px-4 py-2 text-end">{{ __('Qty') }}</th>
                                                <th class="px-4 py-2 text-end">{{ __('Line total (HT)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                            @foreach ($invoice->items as $row)
                                                <tr>
                                                    <td class="px-4 py-3 text-start">{{ $row->description }}</td>
                                                    <td class="px-4 py-3 text-end"><span class="flowdesk-ltr-num tabular-nums">{{ $row->quantity }}</span></td>
                                                    <td class="px-4 py-3 text-end font-medium"><span class="flowdesk-ltr-num tabular-nums font-medium">{{ flowdesk_format_minor((int) $row->total_amount, $ic) }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($invoice->payments->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50">
                            <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Payment history') }}</h3>
                            <ul class="mt-4 space-y-3">
                                @foreach ($invoice->payments as $payment)
                                    <li class="rounded-xl border border-slate-200/80 px-4 py-3 dark:border-slate-700/80">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div>
                                                <p class="font-mono text-sm font-semibold tabular-nums">{{ flowdesk_format_minor((int) $payment->amount, $payment->currency) }} {{ $payment->currency }}</p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    {{ $payment->payment_method?->label() }} · {{ $payment->status?->label() }} · {{ ($payment->paid_at ?? $payment->created_at)?->format('Y-m-d') }}
                                                </p>
                                            </div>
                                            @if ($payment->receipt_path)
                                                <a href="{{ route('portal.payments.receipt', $payment) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                                    <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                                    {{ __('Receipt') }}
                                                </a>
                                            @endif
                                        </div>
                                        @if ($payment->client_notes)
                                            <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ $payment->client_notes }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="xl:col-span-2" id="pay">
                    <div class="sticky top-6 space-y-4">
                        @if ($paymentQr)
                            @include('invoices.partials.payment-qr', ['paymentQr' => $paymentQr, 'compact' => true])
                        @endif

                        @if ($canPay && count($paymentMethods) > 0)
                            <div
                                class="overflow-hidden rounded-2xl border border-indigo-200/80 bg-white shadow-sm dark:border-indigo-900/40 dark:bg-slate-900/50"
                                x-data="portalInvoicePay({
                                    methods: @js($paymentMethods),
                                    balanceMajor: @js(flowdesk_major_amount_for_input($balanceMinor, $ic)),
                                    stripeUrl: @js($hasStripe ? route('portal.invoices.payment-intent', $invoice) : null),
                                    paypalUrl: @js(route('portal.invoices.paypal-order', $invoice)),
                                    flouciUrl: @js(route('portal.invoices.flouci-payment', $invoice)),
                                    stripePublishableKey: @js($stripePk),
                                    bankTransferUrl: @js(route('portal.invoices.bank-transfer', $invoice)),
                                })"
                            >
                                <div class="border-b border-indigo-200/60 bg-gradient-to-br from-indigo-50/90 to-white px-5 py-4 dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-slate-900/50">
                                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Pay this invoice') }}</h3>
                                    <p class="mt-1 text-2xl font-bold tabular-nums text-indigo-700 dark:text-indigo-300">{{ flowdesk_format_minor((int) $balanceMinor, $ic) }} {{ $ic }}</p>
                                </div>

                                <div class="p-5 space-y-4">
                                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('portal_choose_payment_method') }}</p>
                                    <div class="grid gap-2">
                                        <template x-for="method in methods" :key="method.id">
                                            <button
                                                type="button"
                                                @click="selectedMethod = method.id"
                                                class="flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-start text-sm transition"
                                                :class="selectedMethod === method.id ? 'border-indigo-400 bg-indigo-50/80 dark:border-indigo-500 dark:bg-indigo-950/40' : 'border-slate-200/80 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900/40'"
                                            >
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                    <i class="text-base" :class="method.icon" aria-hidden="true"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block font-semibold text-slate-900 dark:text-white" x-text="method.label"></span>
                                                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" x-text="method.description"></span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>

                                    <p class="text-xs text-rose-600 dark:text-rose-400" x-show="err" x-text="err" x-cloak></p>

                                    <div x-show="selectedMethod === 'stripe'" x-cloak class="space-y-3 border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
                                        <div x-show="showStripeForm" x-ref="stripeMount">
                                            <div x-ref="paymentElement"></div>
                                            <button type="button" @click="payStripe()" :disabled="loading" class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900">
                                                {{ __('Confirm card payment') }}
                                            </button>
                                        </div>
                                        <button type="button" x-show="!showStripeForm" @click="payStripe()" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900">
                                            <i class="fa-brands fa-stripe" aria-hidden="true"></i>
                                            {{ __('Continue with card') }}
                                        </button>
                                    </div>

                                    <div x-show="selectedMethod === 'paypal'" x-cloak class="border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
                                        <button type="button" @click="payPayPal()" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#0070ba] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#005ea6] disabled:opacity-50">
                                            <i class="fa-brands fa-paypal" aria-hidden="true"></i>
                                            {{ __('Continue with PayPal') }}
                                        </button>
                                    </div>

                                    <div x-show="selectedMethod === 'flouci'" x-cloak class="border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
                                        <button type="button" @click="payFlouci()" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50">
                                            <i class="fa-solid fa-credit-card" aria-hidden="true"></i>
                                            {{ __('Continue with Flouci') }}
                                        </button>
                                    </div>

                                    <div x-show="selectedMethod === 'bank_transfer'" x-cloak class="space-y-3 border-t border-slate-200/80 pt-4 dark:border-slate-700/80">
                                        @if (! empty($paymentCreds['bank_instructions']))
                                            <div class="rounded-lg border border-slate-200/80 bg-slate-50/80 p-3 text-sm whitespace-pre-wrap text-slate-700 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-300">{{ $paymentCreds['bank_instructions'] }}</div>
                                        @endif
                                        <form method="POST" action="{{ route('portal.invoices.bank-transfer', $invoice) }}" enctype="multipart/form-data" class="space-y-3">
                                            @csrf
                                            <div>
                                                <x-input-label for="bank_amount" :value="__('Amount transferred')" />
                                                <x-text-input id="bank_amount" name="amount" type="text" class="mt-1 block w-full" :value="old('amount', flowdesk_major_amount_for_input($balanceMinor, $ic))" required />
                                                <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                                            </div>
                                            <div>
                                                <x-input-label for="client_notes" :value="__('Transfer reference (optional)')" />
                                                <x-text-input id="client_notes" name="client_notes" type="text" class="mt-1 block w-full" :value="old('client_notes')" />
                                            </div>
                                            <div>
                                                <x-input-label for="receipt" :value="__('Payment receipt')" />
                                                <input id="receipt" name="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="mt-1 block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 dark:text-slate-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-200" />
                                                <p class="mt-1 text-xs text-slate-500">{{ __('portal_receipt_help') }}</p>
                                                <x-input-error :messages="$errors->get('receipt')" class="mt-1" />
                                            </div>
                                            <x-primary-button type="submit" class="w-full justify-center !normal-case">
                                                <i class="fa-solid fa-paper-plane text-sm" aria-hidden="true"></i>
                                                {{ __('Submit transfer proof') }}
                                            </x-primary-button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @elseif ($balanceMinor <= 0)
                            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/80 p-6 text-center dark:border-emerald-900/40 dark:bg-emerald-950/30">
                                <i class="fa-solid fa-circle-check text-2xl text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                                <p class="mt-3 font-semibold text-emerald-900 dark:text-emerald-100">{{ __('portal_invoice_paid') }}</p>
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 text-sm text-slate-600 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:text-slate-400">
                                {{ __('portal_contact_team_to_pay') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($hasStripe && $stripePk)
        @push('scripts')
            <script src="https://js.stripe.com/v3/"></script>
        @endpush
    @endif
</x-app-layout>
