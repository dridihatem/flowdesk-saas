@php
    $doc = $proposal ?? null;
    $isEdit = $doc !== null;
    $formAction = $isEdit ? route('proposals.update', $doc) : route('proposals.store');
    $workspaceCurrency = strtoupper((string) (auth()->user()->company?->default_currency ?? 'USD'));
    $docCurrency = strtoupper((string) old('currency', $doc?->currency ?? $workspaceCurrency));

    if ($isEdit && $doc->items->isNotEmpty()) {
        $defaultItems = $doc->items->map(fn ($row) => [
            'description' => $row->description,
            'quantity' => $row->quantity,
            'unit_amount' => $row->unit_amount,
            'total_amount' => $row->total_amount,
        ])->all();
    } elseif ($isEdit) {
        $defaultItems = [['description' => $doc->name, 'quantity' => 1, 'unit_amount' => (int) $doc->subtotal_amount ?: (int) $doc->amount, 'total_amount' => (int) $doc->subtotal_amount ?: (int) $doc->amount]];
    } else {
        $defaultItems = [['description' => '', 'quantity' => 1, 'unit_amount' => 0, 'total_amount' => 0]];
        if (is_array($assistantPrefill ?? null) && ! empty($assistantPrefill['items'])) {
            $defaultItems = collect($assistantPrefill['items'])->map(function (array $row) use ($docCurrency) {
                $qty = max(1, (int) ($row['quantity'] ?? 1));
                $major = (float) ($row['unit_major'] ?? 0);
                $minor = $major > 0 ? flowdesk_decimal_to_minor((string) $major, $docCurrency) : 0;

                return [
                    'description' => (string) ($row['description'] ?? ''),
                    'quantity' => $qty,
                    'unit_amount' => $minor,
                    'total_amount' => $minor * $qty,
                ];
            })->all();
        }
    }

    $itemsRaw = old('items', $defaultItems);
    $itemsOld = collect($itemsRaw)->map(function ($row) use ($docCurrency) {
        $minor = (int) ($row['unit_amount'] ?? 0);
        if ($minor <= 0) {
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            $lineTotal = (int) ($row['total_amount'] ?? 0);
            if ($lineTotal > 0) {
                $minor = intdiv($lineTotal, $qty);
            }
        }

        return [
            'description' => $row['description'] ?? '',
            'quantity' => (int) ($row['quantity'] ?? 1),
            'unit_amount_minor' => $minor,
            'unit_major' => flowdesk_minor_to_major($minor, $docCurrency),
            'unit_display' => flowdesk_major_amount_for_locale_input($minor, $docCurrency),
        ];
    })->values()->all();

    $currencyMoneyMeta = flowdesk_currency_money_meta_for_js(
        auth()->user()->company?->default_currency ?? 'USD',
        $doc?->currency
    );
    $localeSep = flowdesk_locale_amount_separators();
    $aiConfig = [
        'url' => $isEdit
            ? route('proposals.ai-line-items', $doc)
            : route('proposals.ai-line-items.draft'),
        'scanUrl' => $isEdit
            ? route('proposals.ai-line-items.scan', $doc)
            : route('proposals.ai-line-items.scan.draft'),
        'draft' => ! $isEdit,
        'errBrief' => __('quote_ai_brief_required'),
        'errNetwork' => __('quote_ai_network_error'),
        'errEmpty' => __('quote_ai_empty_lines'),
        'errScanFile' => __('document_scan_file_required'),
        'errScanNetwork' => __('document_scan_network_error'),
        'errScanEmpty' => __('document_scan_empty_lines'),
        'initialBrief' => is_array($assistantPrefill ?? null) ? (string) ($assistantPrefill['outline'] ?? '') : '',
    ];
@endphp

<form
    method="POST"
    action="{{ $formAction }}"
    x-data="quoteForm(@js($itemsOld), @js($taxPreview), @js($currencyMoneyMeta), @js($localeSep), @js($aiConfig), @js(route('clients.quick-store')))"
    class="space-y-0"
    data-client-mode="pick"
    data-quick-error="{{ __('Could not save client.') }}"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_minmax(300px,360px)]">
        {{-- Left: quote details + line items --}}
        <div class="space-y-8">
            <section class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Quote details') }}</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" :value="__('Quote title')" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $doc?->name ?? (is_array($assistantPrefill ?? null) ? ($assistantPrefill['quote_name'] ?? '') : ''))" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-input-label for="client_id" :value="__('Client')" class="mb-0" />
                            <button type="button" class="ms-auto inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="openQuickClientModal()" title="{{ __('Add client to list now') }}">
                                <span>+</span> {{ __('Quick add client') }}
                            </button>
                        </div>
                        <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" required>
                            <option value="">{{ __('Select client') }}</option>
                            @foreach ($clients as $c)
                                <option value="{{ $c->id }}" @selected((string) old('client_id', $doc?->client_id ?? $prefillClientId ?? '') === (string) $c->id)>{{ $c->name }}@if ($c->email) — {{ $c->email }} @endif</option>
                            @endforeach
                            <template x-for="c in extraClients" :key="c.id">
                                <option :value="c.id" x-text="c.name" :selected="c.id === selectedQuickId"></option>
                            </template>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('client_id')" />
                    </div>
                    <div>
                        <x-input-label for="project_id" :value="__('Project')" />
                        <select id="project_id" name="project_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach ($projects as $p)
                                <option value="{{ $p->id }}" @selected((string) old('project_id', $doc?->project_id ?? '') === (string) $p->id)>{{ $p->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="currency" :value="__('Currency')" />
                        <x-currency-select id="currency" name="currency" :options="$currencyOptions" :value="old('currency', $doc?->currency ?? auth()->user()->company?->default_currency ?? 'USD')" x-on:change="syncCurrencyMoney($event.target.value)" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="currencyCode === 'TND' ? @js(__('quote_price_hint_tnd')) : @js(__('quote_price_hint_default'))"></p>
                    </div>
                    <div>
                        <x-input-label for="valid_until" :value="__('Valid until')" />
                        <x-text-input id="valid_until" name="valid_until" type="date" class="mt-1 block w-full" :value="old('valid_until', $doc?->valid_until?->format('Y-m-d') ?? '')" />
                    </div>
                    @if (! empty($referencePreview))
                        <div class="sm:col-span-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Next quote reference (auto)') }}: <span class="font-mono text-slate-700 dark:text-slate-300">{{ $referencePreview }}</span></p>
                        </div>
                    @endif
                </div>
            </section>

            @include('proposals.partials.line-items-ai-panel')

            <section class="rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200/80 px-6 py-4 dark:border-slate-700/80">
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ __('Line items') }}</h3>
                    <button type="button" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" @click="items.push({ description: '', quantity: 1, unit_major: 0, unit_amount_minor: 0, unit_display: pricePlaceholder() }); touchLines()">
                        <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                        {{ __('Add line') }}
                    </button>
                </div>

                <div class="space-y-3 p-6">
                    <template x-for="(line, i) in items" :key="i">
                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                            <div class="grid gap-4 sm:grid-cols-12">
                                <div class="sm:col-span-12">
                                    <label class="block text-xs font-medium text-slate-500" x-bind:for="'q_desc_'+i">{{ __('Description') }}</label>
                                    <input type="text" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" x-model="line.description" x-bind:id="'q_desc_'+i" x-bind:name="'items['+i+'][description]'" required />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-medium text-slate-500" x-bind:for="'q_qty_'+i">{{ __('Qty') }}</label>
                                    <input type="number" min="1" class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm tabular-nums shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" x-model.number="line.quantity" x-bind:id="'q_qty_'+i" x-bind:name="'items['+i+'][quantity]'" @input="onLineQtyInput()" required />
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-slate-500" x-bind:for="'q_unit_'+i">{{ __('Unit price (HT)') }}</label>
                                    <input type="hidden" x-bind:name="'items['+i+'][unit_amount]'" :value="unitMinor(line)" />
                                    <input
                                        type="text"
                                        inputmode="decimal"
                                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm tabular-nums shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                        x-model="line.unit_display"
                                        x-bind:id="'q_unit_'+i"
                                        x-bind:placeholder="pricePlaceholder()"
                                        @input="onLinePriceInput(line)"
                                        @blur="onLinePriceBlur(line)"
                                        required
                                    />
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-slate-500" x-bind:for="'q_line_ht_'+i">{{ __('Line total (HT)') }}</label>
                                    <p
                                        x-bind:id="'q_line_ht_'+i"
                                        class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium tabular-nums text-slate-800 dark:border-slate-600 dark:bg-slate-900/50 dark:text-slate-200"
                                        x-text="fmtMinor(lineHt(line)) + ' ' + currencyCode"
                                    ></p>
                                </div>
                                <div class="sm:col-span-4 flex items-end justify-end">
                                    <button type="button" class="text-sm text-rose-600 hover:text-rose-500" x-show="items.length > 1" @click="items.splice(i, 1); touchLines()">{{ __('Remove') }}</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-200/80 bg-indigo-50/40 px-6 py-5 dark:border-slate-700/80 dark:bg-indigo-950/20">
                    <dl class="ml-auto max-w-sm space-y-2 text-sm">
                        <div class="flex justify-between gap-4 text-slate-700 dark:text-slate-300">
                            <dt>{{ __('Subtotal (ex. VAT)') }}</dt>
                            <dd class="tabular-nums font-medium" x-text="fmtMinor(lineSubtotal) + ' ' + currencyCode"></dd>
                        </div>
                        <div class="flex justify-between gap-4 text-slate-700 dark:text-slate-300" x-show="(Number(taxPreview.vat_percent) || 0) > 0">
                            <dt>{{ __('VAT') }} (<span x-text="taxPreview.vat_percent"></span>%)</dt>
                            <dd class="tabular-nums font-medium" x-text="fmtMinor(totals.vat) + ' ' + currencyCode"></dd>
                        </div>
                        <div class="flex justify-between gap-4 text-slate-700 dark:text-slate-300" x-show="taxPreview.fiscal_stamp_enabled && totals.stamp > 0">
                            <dt>{{ __('Fiscal stamp') }}</dt>
                            <dd class="tabular-nums font-medium" x-text="fmtMinor(totals.stamp) + ' ' + currencyCode"></dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-indigo-200/60 pt-3 text-base font-bold text-slate-900 dark:border-indigo-800/60 dark:text-white">
                            <dt>{{ __('Total (inc. VAT)') }}</dt>
                            <dd class="tabular-nums" x-text="fmtMinor(totals.total) + ' ' + currencyCode"></dd>
                        </div>
                    </dl>
                </div>
                <x-input-error class="px-6 pb-4" :messages="$errors->get('items')" />
            </section>
        </div>

        {{-- Right: notes + actions (sticky) --}}
        <div class="space-y-6 xl:sticky xl:top-24 xl:self-start">
            <section class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm dark:border-slate-700/80 dark:bg-slate-900/40">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Notes') }}</h3>
                <div class="mt-5 space-y-4">
                    <div>
                        <x-input-label for="customer_notes" :value="__('Customer notes (shown on PDF)')" />
                        <textarea id="customer_notes" name="customer_notes" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('customer_notes', $doc?->customer_notes ?? (is_array($assistantPrefill ?? null) ? ($assistantPrefill['outline'] ?? '') : '')) }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="internal_notes" :value="__('Internal notes (team only)')" />
                        <textarea id="internal_notes" name="internal_notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">{{ old('internal_notes', $doc?->internal_notes ?? '') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-5 dark:border-slate-600/60 dark:bg-slate-800/40">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="send_to_client" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800" @checked(old('send_to_client')) />
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ __('Send to client by email') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-600 dark:text-slate-400">{{ __('quote_send_to_client_hint') }}</span>
                    </span>
                </label>
            </section>

            <div class="flex flex-col gap-2">
                <x-primary-button class="justify-center w-full">{{ $isEdit ? __('Save quote') : __('Create quote') }}</x-primary-button>
                <a href="{{ $isEdit ? route('proposals.show', $doc) : route('proposals.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Cancel') }}</a>
            </div>
        </div>
    </div>

    <x-client-document-quick-modal :intro="__('Saves immediately and adds to the list for this quote.')" />
</form>
