@php
    use App\Services\InvoicePaymentGatewayService;
    $gatewayOptions = [
        InvoicePaymentGatewayService::GATEWAY_STRIPE => ['label' => __('Stripe (card)'), 'icon' => 'fa-brands fa-stripe'],
        InvoicePaymentGatewayService::GATEWAY_PAYPAL => ['label' => __('PayPal'), 'icon' => 'fa-brands fa-paypal'],
        InvoicePaymentGatewayService::GATEWAY_FLOUCI => ['label' => __('Flouci'), 'icon' => 'fa-solid fa-credit-card'],
        InvoicePaymentGatewayService::GATEWAY_BANK => ['label' => __('Bank transfer'), 'icon' => 'fa-solid fa-building-columns'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Client payment methods') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/50 p-5 text-sm text-slate-700 dark:border-indigo-900/40 dark:bg-indigo-950/25 dark:text-slate-300">
                {{ __('settings_payment_gateways_intro') }}
            </div>

            <form method="POST" action="{{ route('settings.payment-gateways.update') }}" class="flow-panel space-y-8 p-6 sm:p-8">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Methods offered to clients') }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('settings_payment_gateways_enabled_help') }}</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($gatewayOptions as $id => $meta)
                            @php
                                $ready = match ($id) {
                                    InvoicePaymentGatewayService::GATEWAY_STRIPE => app(InvoicePaymentGatewayService::class)->stripeReady($resolved),
                                    InvoicePaymentGatewayService::GATEWAY_PAYPAL => app(InvoicePaymentGatewayService::class)->paypalReady($resolved),
                                    InvoicePaymentGatewayService::GATEWAY_FLOUCI => app(InvoicePaymentGatewayService::class)->flouciReady($resolved),
                                    InvoicePaymentGatewayService::GATEWAY_BANK => ! empty($resolved['bank_instructions']) || ! empty(old('bank_instructions', $payment['bank_instructions'] ?? '')),
                                    default => false,
                                };
                            @endphp
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200/80 p-4 transition hover:border-indigo-200 dark:border-slate-700 dark:hover:border-indigo-800 {{ ! $ready ? 'opacity-60' : '' }}">
                                <input type="checkbox" name="enabled_gateways[]" value="{{ $id }}" @checked(in_array($id, old('enabled_gateways', $enabled), true)) class="mt-1 rounded border-slate-300 text-indigo-600" {{ ! $ready ? 'disabled' : '' }} />
                                <span>
                                    <span class="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
                                        <i class="{{ $meta['icon'] }} text-slate-500" aria-hidden="true"></i>
                                        {{ $meta['label'] }}
                                    </span>
                                    @if (! $ready)
                                        <span class="mt-1 block text-xs text-amber-700 dark:text-amber-300">{{ __('Configure credentials below or in platform admin.') }}</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200/80 p-5 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Company Stripe keys (optional)') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to use platform keys.') }}</p>
                        <div class="mt-4 space-y-3">
                            <x-text-input name="stripe_public_key" type="text" class="block w-full" :value="old('stripe_public_key', $payment['stripe_public_key'] ?? '')" placeholder="{{ __('Publishable key') }}" />
                            <x-text-input name="stripe_secret_key" type="password" class="block w-full" placeholder="{{ __('Secret key') }}" autocomplete="off" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 p-5 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Company PayPal keys (optional)') }}</p>
                        <div class="mt-4 space-y-3">
                            <x-text-input name="paypal_client_id" type="text" class="block w-full" :value="old('paypal_client_id', $payment['paypal_client_id'] ?? '')" />
                            <x-text-input name="paypal_secret" type="password" class="block w-full" autocomplete="off" />
                            <select name="paypal_mode" class="block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="sandbox" @selected(old('paypal_mode', $payment['paypal_mode'] ?? 'sandbox') === 'sandbox')>{{ __('Sandbox') }}</option>
                                <option value="live" @selected(old('paypal_mode', $payment['paypal_mode'] ?? '') === 'live')>{{ __('Live') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 p-5 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Company Flouci keys (optional)') }}</p>
                        <div class="mt-4 space-y-3">
                            <x-text-input name="flouci_public_key" type="text" class="block w-full" :value="old('flouci_public_key', $payment['flouci_public_key'] ?? '')" />
                            <x-text-input name="flouci_secret_key" type="password" class="block w-full" autocomplete="off" />
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 p-5 dark:border-slate-700">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Bank transfer instructions') }}</p>
                        <textarea name="bank_instructions" rows="6" class="mt-3 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('bank_instructions', $payment['bank_instructions'] ?? ($platform['bank_instructions'] ?? '')) }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Save payment methods') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
