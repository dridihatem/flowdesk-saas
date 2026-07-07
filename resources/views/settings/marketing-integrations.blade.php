<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('marketing_integrations_title') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('mailchimp'))
                <div class="mb-6 rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    {{ $errors->first('mailchimp') }}
                </div>
            @endif
            @if ($errors->has('twilio'))
                <div class="mb-6 rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    {{ $errors->first('twilio') }}
                </div>
            @endif

            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('marketing_integrations_intro') }}</p>

            <div class="mt-8 rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/50 sm:p-8">
                <form method="POST" action="{{ route('settings.marketing-integrations.update') }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Campaign email delivery') }}</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('marketing_integrations_email_modes_help') }}</p>
                        <div class="mt-3 space-y-2 text-sm text-slate-800 dark:text-slate-200">
                            <label class="flex items-start gap-2">
                                <input type="radio" name="campaign_email" value="app_default" @checked($form['campaign_email'] === 'app_default') class="mt-0.5 text-indigo-600" />
                                <span><strong>{{ __('App default or tenant SMTP') }}</strong> — {{ __('uses_workspace_smtp_if_set') }}</span>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="campaign_email" value="tenant_smtp" @checked($form['campaign_email'] === 'tenant_smtp') class="mt-0.5 text-indigo-600" />
                                <span><strong>{{ __('Tenant SMTP only') }}</strong> — {{ __('campaigns_error_if_smtp_empty') }}</span>
                            </label>
                            <label class="flex items-start gap-2">
                                <input type="radio" name="campaign_email" value="sendgrid" @checked($form['campaign_email'] === 'sendgrid') class="mt-0.5 text-indigo-600" />
                                <span><strong>SendGrid</strong> — {{ __('SendGrid API key for campaigns') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">SendGrid</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('SendGrid API key (full access) stored encrypted.') }}</p>
                        <div class="mt-3">
                            <x-input-label for="sendgrid_api_key" :value="__('API key')" />
                            <x-text-input
                                id="sendgrid_api_key"
                                name="sendgrid_api_key"
                                type="password"
                                class="mt-1 block w-full"
                                :value="''"
                                autocomplete="off"
                            />
                            <label class="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                                <input type="checkbox" name="clear_sendgrid_api_key" value="1" class="rounded border-slate-300" />
                                {{ __('Remove stored SendGrid key') }}
                            </label>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Mailchimp</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('mailchimp_settings_help') }}</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-input-label for="mailchimp_api_key" :value="__('API key')" />
                                <x-text-input id="mailchimp_api_key" name="mailchimp_api_key" type="password" class="mt-1 block w-full" value="" autocomplete="off" />
                            </div>
                            <div>
                                <x-input-label for="mailchimp_server_prefix" :value="__('Server prefix (e.g. us1)')" />
                                <x-text-input
                                    id="mailchimp_server_prefix"
                                    name="mailchimp_server_prefix"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('mailchimp_server_prefix', $form['mailchimp_server_prefix'] ?? '')"
                                />
                            </div>
                            <div>
                                <x-input-label for="mailchimp_list_id" :value="__('Default list ID (optional)')" />
                                <x-text-input
                                    id="mailchimp_list_id"
                                    name="mailchimp_list_id"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('mailchimp_list_id', $form['mailchimp_list_id'] ?? '')"
                                />
                            </div>
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="clear_mailchimp_api_key" value="1" class="rounded border-slate-300" />
                            {{ __('Remove stored Mailchimp key') }}
                        </label>
                    </div>

                    <div class="rounded-xl border border-slate-200/60 bg-slate-50/50 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Twilio (SMS)</h3>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('twilio_settings_help') }}</p>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="twilio_account_sid" :value="__('Account SID')" />
                                <x-text-input
                                    id="twilio_account_sid"
                                    name="twilio_account_sid"
                                    type="text"
                                    class="mt-1 block w-full font-mono text-sm"
                                    :value="old('twilio_account_sid', $form['twilio_account_sid'] ?? '')"
                                />
                            </div>
                            <div>
                                <x-input-label for="twilio_from" :value="__('From number (E.164)')" />
                                <x-text-input
                                    id="twilio_from"
                                    name="twilio_from"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :value="old('twilio_from', $form['twilio_from'] ?? '')"
                                    placeholder="+1..."
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="twilio_auth_token" :value="__('Auth token')" />
                                <x-text-input
                                    id="twilio_auth_token"
                                    name="twilio_auth_token"
                                    type="password"
                                    class="mt-1 block w-full"
                                    value=""
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="clear_twilio_token" value="1" class="rounded border-slate-300" />
                            {{ __('Remove stored Twilio token') }}
                        </label>
                    </div>

                    <x-primary-button type="submit">{{ __('Save marketing integrations') }}</x-primary-button>
                </form>

                <div class="mt-10 flex flex-wrap gap-4 border-t border-slate-200 pt-6 dark:border-slate-700">
                    <form method="POST" action="{{ route('settings.marketing-integrations.mailchimp-test') }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Test Mailchimp connection') }}</x-secondary-button>
                    </form>
                    <form method="POST" action="{{ route('settings.marketing-integrations.sms-test') }}" class="flex flex-wrap items-end gap-2" onsubmit="return this.querySelector('input[name=to]').value.trim() !== ''">
                        @csrf
                        <div>
                            <x-input-label for="sms_to" :value="__('Test SMS to')" />
                            <x-text-input id="sms_to" name="to" type="text" class="mt-1 block w-full" placeholder="+1..." />
                        </div>
                        <x-primary-button type="submit">{{ __('Send test SMS') }}</x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
