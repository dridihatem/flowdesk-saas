@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    if (request()->query('module_action') === 'store_order') {
        $num = trim((string) request()->query('order_number', ''));
        if ($num !== '') {
            $orderId = (string) Str::ulid();
            $lineLabel = trim((string) request()->query('line_label', 'Item'));
            $qty = max(1, (int) request()->query('qty', 1));
            $unit = max(0, (int) request()->query('unit_price_qar', 0));
            DB::table('module_orders')->insert([
                'id' => $orderId,
                'company_id' => $companyId,
                'client_id' => request()->query('client_id') ?: null,
                'order_number' => $num,
                'channel' => request()->query('channel') ?: 'website',
                'status' => 'new',
                'payment_method' => request()->query('payment_method') ?: 'cod',
                'total_qar' => $qty * $unit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('module_order_lines')->insert([
                'id' => (string) Str::ulid(),
                'order_id' => $orderId,
                'label' => $lineLabel,
                'qty' => $qty,
                'unit_price_qar' => $unit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    $orders = DB::table('module_orders')->where('company_id', $companyId)->orderByDesc('created_at')->get();
    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(100)->get(['id', 'name']);
@endphp

<div class="space-y-6" data-qatar-module="orders-inbox">
    <div class="flow-panel p-6 flex flex-wrap gap-2">
        <a href="{{ route('invoices.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Invoices') }}</a>
        <a href="{{ route('modules.show', 'qatar-catalog-lite') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Catalog') }}</a>
        <a href="{{ route('modules.show', 'qatar-delivery-dispatch') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Delivery') }}</a>
    </div>
    <div class="flow-panel p-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_order">
            <input name="order_number" required placeholder="{{ __('Order #') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <select name="channel" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
                <option value="website">Website</option><option value="instagram">Instagram</option><option value="whatsapp">WhatsApp</option><option value="snoonu">Snoonu</option>
            </select>
            <select name="payment_method" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"><option value="cod">COD</option><option value="card">Card</option><option value="online">Online</option></select>
            <select name="client_id" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"><option value="">{{ __('Client') }}</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            <input name="line_label" placeholder="{{ __('Line item') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="qty" type="number" value="1" min="1" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="unit_price_qar" type="number" placeholder="QAR" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <button type="submit" class="sm:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Add order') }}</button>
        </form>
    </div>
    <div class="flow-panel p-0 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2">{{ __('#') }}</th><th class="px-4 py-2">{{ __('Channel') }}</th><th class="px-4 py-2">{{ __('Status') }}</th><th class="px-4 py-2">QAR</th><th class="px-4 py-2">{{ __('Client') }}</th></tr></thead>
            <tbody>@foreach($orders as $o)<tr class="border-t dark:border-slate-800"><td class="px-4 py-2 font-mono">{{ $o->order_number }}</td><td class="px-4 py-2">{{ $o->channel }}</td><td class="px-4 py-2"><span class="rounded bg-slate-100 px-2 text-[10px] font-bold uppercase dark:bg-slate-800">{{ $o->status }}</span></td><td class="px-4 py-2">{{ number_format($o->total_qar) }}</td><td class="px-4 py-2">@if($o->client_id)<a href="{{ route('clients.edit', $o->client_id) }}" class="text-indigo-600">{{ $clients->firstWhere('id', $o->client_id)?->name }}</a>@endif</td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
