<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('New invoice') }}</h2>
    </x-slot>

    @php
        $prefillItems = isset($invoiceProjectPrefill) && is_array($invoiceProjectPrefill['items'] ?? null) ? $invoiceProjectPrefill['items'] : null;
        $itemsRaw = old('items', $prefillItems ?? [['description' => '', 'quantity' => 1, 'unit_amount' => 0]]);
        $workspaceCurrency = strtoupper((string) (auth()->user()->company?->default_currency ?? 'USD'));
        $invoiceCurrency = strtoupper((string) old('currency', $workspaceCurrency));
        $usedProjectPrefillLines = ! session()->hasOldInput('items')
            && ! empty(($invoiceProjectPrefill ?? [])['project_id'] ?? null);
        $minorCurrencyForLines = $usedProjectPrefillLines ? $workspaceCurrency : $invoiceCurrency;
        $itemsOld = collect($itemsRaw)->map(function ($row) use ($minorCurrencyForLines) {
            $minor = (int) ($row['unit_amount'] ?? 0);

            return [
                'description' => $row['description'] ?? '',
                'quantity' => (int) ($row['quantity'] ?? 1),
                'unit_amount_minor' => $minor,
                'unit_major' => flowdesk_minor_to_major($minor, $minorCurrencyForLines),
            ];
        })->values()->all();
        $currencyMoneyMeta = flowdesk_currency_money_meta_for_js(
            auth()->user()->company?->default_currency ?? 'USD',
            $invoiceCurrency
        );
        $invoiceAiConfig = [
            'url' => route('invoices.ai-line-items.draft'),
            'scanUrl' => route('invoices.ai-line-items.scan.draft'),
            'draft' => true,
            'errBrief' => __('invoice_ai_brief_required'),
            'errNetwork' => __('invoice_ai_network_error'),
            'errEmpty' => __('invoice_ai_empty_lines'),
            'errScanFile' => __('document_scan_file_required'),
            'errScanNetwork' => __('document_scan_network_error'),
            'errScanEmpty' => __('document_scan_empty_lines'),
        ];
    @endphp

    <div class="py-10">
        <div class="max-w-7xl w-full sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/40 dark:ring-white/[0.06]">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 to-white px-8 py-5 dark:border-slate-700/80 dark:from-slate-800/40 dark:to-slate-900/40">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('New invoice') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('invoice_create_lead') }}</p>
                </div>
                <div class="p-8">
                <form
                    method="POST"
                    action="{{ route('invoices.store') }}"
                    class="space-y-8"
                    x-data="invoiceForm(@js($itemsOld), @js($taxPreview), @js(route('clients.quick-store')), @js($currencyMoneyMeta), @js(flowdesk_locale_amount_separators()), @js($invoiceAiConfig))"
                    data-client-mode="{{ old('client_mode', 'pick') }}"
                    data-quick-error="{{ __('Could not save client.') }}"
                >
                    @csrf
                    @if (! empty($invoiceProjectPrefill['project_id'] ?? null))
                        <input type="hidden" name="project_id" value="{{ $invoiceProjectPrefill['project_id'] }}" />
                    @endif
                    <input type="hidden" name="client_mode" :value="clientMode" />

                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Client') }}</span>
                            <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" @click="clientMode = 'pick'">{{ __('Select existing') }}</button>
                            <span class="text-slate-400">|</span>
                            <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" @click="clientMode = 'new'">{{ __('Create new on save') }}</button>
                            <button type="button" class="ms-auto inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="openQuickClientModal()" type="button" title="{{ __('Add client to list now') }}">
                                <span>+</span> {{ __('Quick add client') }}
                            </button>
                        </div>

                        <div x-show="clientMode === 'pick'" x-cloak>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="client_id">{{ __('Choose from list') }}</label>
                            <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                                <option value="">{{ __('— Optional —') }}</option>
                                @foreach ($clients as $c)
                                    <option value="{{ $c->id }}" @selected((string) ($prefillClientId ?? '') === (string) $c->id)>{{ $c->name }}@if ($c->code) ({{ $c->code }}) @endif</option>
                                @endforeach
                                <template x-for="c in extraClients" :key="c.id">
                                    <option :value="c.id" x-text="c.name" :selected="c.id === selectedQuickId"></option>
                                </template>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
                        </div>

                        <div x-show="clientMode === 'new'" x-cloak class="space-y-4 rounded-xl border border-slate-200/80 p-4 dark:border-slate-600/60">
                            <div>
                                <x-input-label for="new_client_name" :value="__('New client name')" />
                                <x-text-input id="new_client_name" name="new_client_name" type="text" class="mt-1 block w-full" :value="old('new_client_name')" />
                                <x-input-error class="mt-2" :messages="$errors->get('new_client_name')" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="new_client_email" :value="__('Email')" />
                                    <x-text-input id="new_client_email" name="new_client_email" type="email" class="mt-1 block w-full" :value="old('new_client_email')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('new_client_email')" />
                                </div>
                                <div>
                                    <x-input-label for="new_client_phone" :value="__('Phone')" />
                                    <x-text-input id="new_client_phone" name="new_client_phone" type="text" class="mt-1 block w-full" :value="old('new_client_phone')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('new_client_phone')" />
                                </div>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                                    <input type="hidden" name="create_client_account" value="0" />
                                    <input type="checkbox" name="create_client_account" value="1" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600" @checked(old('create_client_account')) />
                                    {{ __('Create login account for this client') }}
                                </label>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="new_client_password" :value="__('Password')" />
                                    <x-text-input id="new_client_password" name="new_client_password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                    <x-input-error class="mt-2" :messages="$errors->get('new_client_password')" />
                                </div>
                                <div>
                                    <x-input-label for="new_client_password_confirmation" :value="__('Confirm password')" />
                                    <x-text-input id="new_client_password_confirmation" name="new_client_password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-client-document-quick-modal :intro="__('Saves immediately and adds to the list for this invoice.')" />

                    <div class="mt-10 border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="currency" :value="__('Currency')" />
                            <x-currency-select
                                id="currency"
                                name="currency"
                                :options="$currencyOptions"
                                :value="old('currency', auth()->user()->company?->default_currency ?? 'USD')"
                                x-on:change="syncCurrencyMoney($event.target.value)"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('currency')" />
                        </div>
                        <div>
                            <x-input-label for="due_date" :value="__('Due date')" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date')" />
                            <x-input-error class="mt-2" :messages="$errors->get('due_date')" />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                                @foreach (\App\Enums\InvoiceStatus::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('status', 'draft') === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>
                    </div>

                    <div class="mt-10">
                        @include('invoices.partials.line-items-ai-panel')
                    </div>

                    <div class="mt-10 border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
                    <div>
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Line items') }}</h3>
                            <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" @click="items.push({ description: '', quantity: 1, unit_major: 0, unit_amount_minor: 0, unit_display: pricePlaceholder() }); touchLines()">{{ __('Add line') }}</button>
                        </div>
                        <div class="mt-4 space-y-4">
                            <template x-for="(line, i) in items" :key="i">
                                <div class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700">
                                    <div class="grid gap-4 sm:grid-cols-12">
                                        <div class="sm:col-span-12">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'desc_'+i">{{ __('Description') }}</label>
                                            <input type="text" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" x-model="line.description" x-bind:id="'desc_'+i" x-bind:name="'items['+i+'][description]'" required />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'qty_'+i">{{ __('Qty') }}</label>
                                            <input type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" x-model.number="line.quantity" x-bind:id="'qty_'+i" x-bind:name="'items['+i+'][quantity]'" @input="onLineQtyInput()" required />
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'unit_'+i">{{ __('Unit price (HT)') }}</label>
                                            <input type="hidden" x-bind:name="'items['+i+'][unit_amount]'" x-bind:value="unitMinor(line)" />
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm tabular-nums dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                                x-model="line.unit_display"
                                                x-bind:id="'unit_'+i"
                                                x-bind:placeholder="pricePlaceholder()"
                                                @input="onLinePriceInput(line)"
                                                @blur="onLinePriceBlur(line)"
                                                required
                                            />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'line_ht_'+i">{{ __('Line total (HT)') }}</label>
                                            <p
                                                x-bind:id="'line_ht_'+i"
                                                class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 tabular-nums text-sm font-medium text-slate-800 dark:border-slate-600 dark:bg-slate-900/50 dark:text-slate-200"
                                                x-text="fmtMinor(lineHt(line))"
                                            ></p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'line_ttc_'+i">{{ __('Line total (TTC)') }}</label>
                                            <p
                                                x-bind:id="'line_ttc_'+i"
                                                class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 tabular-nums text-sm font-medium text-slate-800 dark:border-slate-600 dark:bg-slate-900/50 dark:text-slate-200"
                                                x-text="fmtMinor(lineTtc(line))"
                                            ></p>
                                        </div>
                                        <div class="sm:col-span-3 flex items-end justify-end">
                                            <button type="button" class="text-sm text-rose-600 hover:text-rose-500" x-show="items.length > 1" @click="items.splice(i, 1); touchLines()">{{ __('Remove') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ __('Unit price is ex. VAT (HT) in your invoice currency, using normal decimals for that currency (e.g. 1.250 for TND). Line TTC splits VAT by line for preview.') }}</p>
                        <div class="mt-4 rounded-xl border border-indigo-200/80 bg-indigo-50/50 p-4 text-sm dark:border-indigo-900/40 dark:bg-indigo-950/20">
                            <p class="font-medium text-slate-800 dark:text-slate-200">{{ __('Estimated totals (workspace VAT & stamp)') }}</p>
                            <dl class="mt-2 grid gap-1 text-slate-700 dark:text-slate-300">
                                <div class="flex justify-between gap-4"><dt>{{ __('Subtotal (ex. VAT)') }}</dt><dd class="tabular-nums" x-text="fmtMinor(totals.subtotal)"></dd></div>
                                <div class="flex justify-between gap-4"><dt>{{ __('VAT') }}</dt><dd class="tabular-nums" x-text="fmtMinor(totals.vat)"></dd></div>
                                <div class="flex justify-between gap-4"><dt>{{ __('Fiscal stamp') }}</dt><dd class="tabular-nums" x-text="fmtMinor(totals.stamp)"></dd></div>
                                <div class="flex justify-between gap-4 border-t border-indigo-200/60 pt-2 font-semibold dark:border-indigo-800/60"><dt>{{ __('Total (inc. VAT)') }}</dt><dd class="tabular-nums" x-text="fmtMinor(totals.total)"></dd></div>
                            </dl>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('items')" />
                    </div>
                    </div>

                    <div class="mt-10 border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="customer_notes" :value="__('Customer notes (shown on PDF)')" />
                            <textarea id="customer_notes" name="customer_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('customer_notes') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('customer_notes')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="internal_notes" :value="__('Internal notes (team only)')" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('internal_notes') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('internal_notes')" />
                        </div>
                    </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <x-primary-button>{{ __('Save invoice') }}</x-primary-button>
                        <x-flow.show-action-button :href="route('invoices.index')" variant="back">{{ __('Cancel') }}</x-flow.show-action-button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
