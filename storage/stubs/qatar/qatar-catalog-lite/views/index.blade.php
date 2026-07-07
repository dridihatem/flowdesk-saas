@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    if (request()->query('module_action') === 'store_product') {
        $sku = trim((string) request()->query('sku', ''));
        $name = trim((string) request()->query('name', ''));
        if ($sku !== '' && $name !== '') {
            DB::table('module_products')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'sku' => $sku,
                'name' => $name,
                'category' => request()->query('category') ?: null,
                'price_qar' => max(0, (int) request()->query('price_qar', 0)),
                'stock_qty' => (int) request()->query('stock_qty', 0),
                'description' => request()->query('description') ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    $products = DB::table('module_products')->where('company_id', $companyId)->orderBy('name')->get();
@endphp

<div class="space-y-6" data-qatar-module="catalog-lite">
    <div class="flow-panel p-6 flex flex-wrap gap-2">
        <a href="{{ route('invoices.create') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('New invoice') }}</a>
        <a href="{{ route('modules.show', 'qatar-orders-inbox') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Orders inbox') }}</a>
    </div>
    <div class="flow-panel p-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_product">
            <input name="sku" required placeholder="SKU" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="name" required placeholder="{{ __('Product name') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="category" placeholder="{{ __('Category') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="price_qar" type="number" placeholder="QAR" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="stock_qty" type="number" value="0" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <textarea name="description" rows="2" placeholder="{{ __('Description') }}" class="sm:col-span-2 rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"></textarea>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Add product') }}</button>
        </form>
    </div>
    <div class="flow-panel p-0 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2 text-start">SKU</th><th class="px-4 py-2">{{ __('Name') }}</th><th class="px-4 py-2">QAR</th><th class="px-4 py-2">{{ __('Stock') }}</th></tr></thead>
            <tbody>@foreach($products as $p)<tr class="border-t dark:border-slate-800"><td class="px-4 py-2 font-mono text-xs">{{ $p->sku }}</td><td class="px-4 py-2">{{ $p->name }}</td><td class="px-4 py-2">{{ number_format($p->price_qar) }}</td><td class="px-4 py-2">{{ $p->stock_qty }}</td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
