@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    if (request()->query('module_action') === 'store_delivery') {
        $ref = trim((string) request()->query('reference', ''));
        if ($ref !== '') {
            DB::table('module_deliveries')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'client_id' => request()->query('client_id') ?: null,
                'reference' => $ref,
                'zone' => request()->query('zone') ?: null,
                'courier_name' => request()->query('courier_name') ?: null,
                'cod_qar' => max(0, (int) request()->query('cod_qar', 0)),
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    $deliveries = DB::table('module_deliveries')->where('company_id', $companyId)->orderByDesc('created_at')->get();
    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(100)->get(['id', 'name']);
@endphp

<div class="space-y-6" data-qatar-module="delivery-dispatch">
    <div class="flow-panel p-6 flex flex-wrap gap-2">
        <a href="{{ route('modules.show', 'qatar-orders-inbox') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Orders') }}</a>
        <a href="{{ route('modules.show', 'qatar-cod-reconciliation') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('COD reconciliation') }}</a>
    </div>
    <div class="flow-panel p-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_delivery">
            <input name="reference" required placeholder="{{ __('Order / stop ref') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="zone" placeholder="{{ __('Doha zone') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="courier_name" placeholder="{{ __('Courier') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="cod_qar" type="number" placeholder="COD QAR" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <select name="client_id" class="sm:col-span-2 rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"><option value="">{{ __('Client') }}</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Assign delivery') }}</button>
        </form>
    </div>
    <div class="flow-panel p-0 overflow-x-auto">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2">{{ __('Ref') }}</th><th class="px-4 py-2">{{ __('Zone') }}</th><th class="px-4 py-2">{{ __('Courier') }}</th><th class="px-4 py-2">COD</th><th class="px-4 py-2">{{ __('Status') }}</th></tr></thead>
        <tbody>@foreach($deliveries as $d)<tr class="border-t dark:border-slate-800"><td class="px-4 py-2 font-mono">{{ $d->reference }}</td><td class="px-4 py-2">{{ $d->zone }}</td><td class="px-4 py-2">{{ $d->courier_name }}</td><td class="px-4 py-2">{{ number_format($d->cod_qar) }}</td><td class="px-4 py-2">{{ $d->status }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
