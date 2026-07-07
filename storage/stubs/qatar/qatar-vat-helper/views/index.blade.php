<div class="space-y-6" data-qatar-module="vat-helper" x-data="{
    net: 1000,
    rate: 5,
    get vat() { return Math.round(this.net * this.rate) / 100; },
    get gross() { return Number(this.net) + this.vat; }
}">
    <div class="flow-panel p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $module->name }}</h3>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $module->description }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('invoices.index') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Invoices') }}</a>
            <a href="{{ route('invoices.create') }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('New invoice') }}</a>
        </div>
    </div>

    <div class="flow-panel p-6 grid gap-4 sm:grid-cols-3">
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('Net amount (QAR)') }}</label>
            <input type="number" x-model.number="net" min="0" step="0.01" class="mt-1 w-full rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ __('VAT rate %') }}</label>
            <input type="number" x-model.number="rate" min="0" max="100" step="0.1" class="mt-1 w-full rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
        </div>
        <div class="rounded-xl bg-indigo-50 p-4 dark:bg-indigo-950/30">
            <p class="text-xs text-slate-500">{{ __('VAT amount') }}</p>
            <p class="text-xl font-bold text-indigo-700 dark:text-indigo-300" x-text="vat.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' QAR'"></p>
            <p class="mt-2 text-xs text-slate-500">{{ __('Gross') }}</p>
            <p class="font-semibold text-slate-900 dark:text-white" x-text="gross.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' QAR'"></p>
        </div>
    </div>

    <div class="flow-panel p-6 text-sm text-slate-600 dark:text-slate-400">
        <p>{{ __('Use FlowDesk invoices for compliant PDF documents. This module does not file with Qatar FTA — it helps your team compute 5% VAT on quotes and POS totals.') }}</p>
    </div>
</div>
