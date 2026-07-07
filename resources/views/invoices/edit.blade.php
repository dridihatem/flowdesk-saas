<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('Edit invoice') }}</h2>
    </x-slot>

    @php
        $itemsDefault = $invoice->items->map(fn ($i) => [
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit_amount' => $i->unit_amount,
        ])->values()->all();
        $itemsRaw = old('items', $itemsDefault);
        $invoiceCurrency = strtoupper((string) old('currency', flowdesk_invoice_currency($invoice)));
        $itemsOld = collect($itemsRaw)->map(function ($row) use ($invoiceCurrency) {
            $minor = (int) ($row['unit_amount'] ?? 0);

            return [
                'description' => $row['description'] ?? '',
                'quantity' => (int) ($row['quantity'] ?? 1),
                'unit_amount_minor' => $minor,
                'unit_major' => flowdesk_minor_to_major($minor, $invoiceCurrency),
            ];
        })->values()->all();
        $company = auth()->user()->company;
        $currencyMoneyMeta = flowdesk_currency_money_meta_for_js($company?->default_currency ?? 'USD', $invoice->currency);
        $invoiceAiConfig = [
            'url' => route('invoices.ai-line-items', $invoice),
            'scanUrl' => route('invoices.ai-line-items.scan', $invoice),
            'draft' => false,
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Edit invoice') }}</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('Adjust lines, client, and tax is recalculated on save.') }}</p>
                </div>
                <div class="p-8">
                <form
                    method="POST"
                    action="{{ route('invoices.update', $invoice) }}"
                    class="space-y-8"
                    x-data="invoiceForm(@js($itemsOld), @js($taxPreview), '', @js($currencyMoneyMeta), @js(flowdesk_locale_amount_separators()), @js($invoiceAiConfig))"
                >
                    @csrf
                    @method('PUT')
                    <div class="space-y-8">
                    <div>
                        <x-input-label for="number" :value="__('Invoice reference')" />
                        <x-text-input id="number" name="number" type="text" class="mt-1 block w-full" :value="old('number', $invoice->number)" maxlength="64" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Shown on PDFs and emails. Leave blank to clear (not recommended). New invoices use your') }} <a href="{{ route('settings.invoice-documents') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">{{ __('invoice document') }}</a> {{ __('settings') }} ({{ __('e.g.') }} {{ $referencePreview }}).</p>
                        <x-input-error class="mt-2" :messages="$errors->get('number')" />
                    </div>
                    <div>
                        <x-input-label for="client_id" :value="__('Client')" />
                        <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">{{ __('— Optional —') }}</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}" @selected(old('client_id', $invoice->client_id) === $c->id)>{{ $c->name }}@if ($c->code) ({{ $c->code }}) @endif</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
                    </div>

                    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="currency" :value="__('Currency')" />
                            <x-currency-select
                                id="currency"
                                name="currency"
                                :options="$currencyOptions"
                                :value="old('currency', $invoice->currency)"
                                x-on:change="syncCurrencyMoney($event.target.value)"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('currency')" />
                        </div>
                        <div>
                            <x-input-label for="due_date" :value="__('Due date')" />
                            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full" :value="old('due_date', $invoice->due_date?->format('Y-m-d'))" />
                            <x-input-error class="mt-2" :messages="$errors->get('due_date')" />
                        </div>
                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                                @foreach (\App\Enums\InvoiceStatus::cases() as $case)
                                    <option value="{{ $case->value }}" @selected(old('status', $invoice->status->value) === $case->value)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('status')" />
                        </div>
                    </div>
                    </div>

                    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
                        @include('invoices.partials.line-items-ai-panel')
                    </div>

                    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
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
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'ed_desc_'+i">{{ __('Description') }}</label>
                                            <input type="text" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" x-model="line.description" x-bind:id="'ed_desc_'+i" x-bind:name="'items['+i+'][description]'" required />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'ed_qty_'+i">{{ __('Qty') }}</label>
                                            <input type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" x-model.number="line.quantity" x-bind:id="'ed_qty_'+i" x-bind:name="'items['+i+'][quantity]'" @input="onLineQtyInput()" required />
                                        </div>
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'ed_unit_'+i">{{ __('Unit price (HT)') }}</label>
                                            <input type="hidden" x-bind:name="'items['+i+'][unit_amount]'" x-bind:value="unitMinor(line)" />
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm tabular-nums dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                                x-model="line.unit_display"
                                                x-bind:id="'ed_unit_'+i"
                                                x-bind:placeholder="pricePlaceholder()"
                                                @input="onLinePriceInput(line)"
                                                @blur="onLinePriceBlur(line)"
                                                required
                                            />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'ed_line_ht_'+i">{{ __('Line total (HT)') }}</label>
                                            <p
                                                x-bind:id="'ed_line_ht_'+i"
                                                class="mt-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 tabular-nums text-sm font-medium text-slate-800 dark:border-slate-600 dark:bg-slate-900/50 dark:text-slate-200"
                                                x-text="fmtMinor(lineHt(line))"
                                            ></p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs font-medium text-slate-500" x-bind:for="'ed_line_ttc_'+i">{{ __('Line total (TTC)') }}</label>
                                            <p
                                                x-bind:id="'ed_line_ttc_'+i"
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
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            {{ __('VAT and fiscal stamp are applied automatically on save using') }}
                            <a href="{{ route('settings.billing-tax') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('billing & tax settings') }}</a>.
                        </p>
                        <x-input-error class="mt-2" :messages="$errors->get('items')" />
                    </div>
                    </div>

                    <div class="border-t border-slate-200/80 pt-8 dark:border-slate-700/80">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="customer_notes" :value="__('Customer notes (shown on PDF)')" />
                            <textarea id="customer_notes" name="customer_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('customer_notes', $invoice->customer_notes) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('customer_notes')" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="internal_notes" :value="__('Internal notes (team only)')" />
                            <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('internal_notes', $invoice->internal_notes) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('internal_notes')" />
                        </div>
                    </div>
                    </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <x-primary-button>{{ __('Update invoice') }}</x-primary-button>
                        <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancel') }}</a>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
