<x-admin-layout>
    <x-flow.page-header
        :title="__('Payment gateways')"
        :description="__('Configure platform-wide API keys. Each company workspace chooses which methods to offer clients and may override keys or bank instructions.')"
    />

    <div class="flow-panel p-8">
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Store keys securely. Secrets are not shown after save; leave blank to keep existing values.') }}</p>

        <form method="POST" action="{{ route('admin.payment-gateways.update') }}" class="mt-8 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Stripe') }}</p>
                        <i class="fa-brands fa-stripe text-slate-400" aria-hidden="true"></i>
                    </div>
                    <div class="mt-4 space-y-4">
                        <div>
                            <x-input-label for="stripe_public_key" :value="__('Publishable key')" />
                            <x-text-input id="stripe_public_key" name="stripe_public_key" type="text" class="mt-1 block w-full" :value="old('stripe_public_key', $payment['stripe_public_key'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="stripe_secret_key" :value="__('Secret key')" />
                            <x-text-input id="stripe_secret_key" name="stripe_secret_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                        </div>
                        <div>
                            <x-input-label for="stripe_webhook_secret" :value="__('Webhook signing secret (optional here)')" />
                            <x-text-input id="stripe_webhook_secret" name="stripe_webhook_secret" type="password" class="mt-1 block w-full" value="" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('For platform SaaS webhooks, prefer STRIPE_WEBHOOK_SECRET in .env.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('PayPal') }}</p>
                        <i class="fa-brands fa-paypal text-slate-400" aria-hidden="true"></i>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="paypal_client_id" :value="__('Client ID')" />
                            <x-text-input id="paypal_client_id" name="paypal_client_id" type="text" class="mt-1 block w-full" :value="old('paypal_client_id', $payment['paypal_client_id'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="paypal_secret" :value="__('Secret')" />
                            <x-text-input id="paypal_secret" name="paypal_secret" type="password" class="mt-1 block w-full" value="" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="paypal_mode" :value="__('Environment')" />
                            <select id="paypal_mode" name="paypal_mode" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="sandbox" @selected(old('paypal_mode', $payment['paypal_mode'] ?? 'sandbox') === 'sandbox')>{{ __('Sandbox') }}</option>
                                <option value="live" @selected(old('paypal_mode', $payment['paypal_mode'] ?? '') === 'live')>{{ __('Live') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('Flouci (Tunisia)') }}</p>
                        <i class="fa-solid fa-credit-card text-slate-400" aria-hidden="true"></i>
                    </div>
                    <div class="mt-4 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="flouci_public_key" :value="__('Public key')" />
                                <x-text-input id="flouci_public_key" name="flouci_public_key" type="text" class="mt-1 block w-full" :value="old('flouci_public_key', $payment['flouci_public_key'] ?? '')" />
                            </div>
                            <div>
                                <x-input-label for="flouci_secret_key" :value="__('Private key')" />
                                <x-text-input id="flouci_secret_key" name="flouci_secret_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                            </div>
                        </div>
                        <div>
                            <x-input-label for="flouci_api_base" :value="__('API base URL (optional)')" />
                            <x-text-input id="flouci_api_base" name="flouci_api_base" type="url" class="mt-1 block w-full" :value="old('flouci_api_base', $payment['flouci_api_base'] ?? '')" placeholder="https://developers.flouci.com/api/v2/generate_payment" />
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ __('Webhook:') }} <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">{{ url('/webhooks/flouci') }}</code></p>
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm lg:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">{{ __('admin_payment_bank_transfer_title') }}</p>
                        <i class="fa-solid fa-building-columns text-slate-400" aria-hidden="true"></i>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ __('admin_payment_bank_transfer_hint') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="bank_account_holder" :value="__('admin_payment_bank_account_holder')" />
                            <x-text-input id="bank_account_holder" name="bank_account_holder" type="text" class="mt-1 block w-full" :value="old('bank_account_holder', $payment['bank_account_holder'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="bank_name" :value="__('admin_payment_bank_name')" />
                            <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" :value="old('bank_name', $payment['bank_name'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="bank_rib" :value="__('admin_payment_bank_rib')" />
                            <x-text-input id="bank_rib" name="bank_rib" type="text" class="mt-1 block w-full font-mono" :value="old('bank_rib', $payment['bank_rib'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="bank_bic" :value="__('admin_payment_bank_bic')" />
                            <x-text-input id="bank_bic" name="bank_bic" type="text" class="mt-1 block w-full font-mono" :value="old('bank_bic', $payment['bank_bic'] ?? '')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="bank_instructions" :value="__('admin_payment_bank_extra_instructions')" />
                            <textarea id="bank_instructions" name="bank_instructions" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" placeholder="{{ __('admin_payment_bank_extra_instructions_placeholder') }}">{{ old('bank_instructions', $payment['bank_instructions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <x-primary-button>{{ __('Save payment gateways') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-admin-layout>
