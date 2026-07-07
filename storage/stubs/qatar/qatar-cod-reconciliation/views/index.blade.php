@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    if (request()->query('module_action') === 'store_cod') {
        $courier = trim((string) request()->query('courier_name', ''));
        if ($courier !== '') {
            $expected = max(0, (int) request()->query('expected_qar', 0));
            $received = max(0, (int) request()->query('received_qar', 0));
            DB::table('module_cod_collections')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'courier_name' => $courier,
                'collection_date' => request()->query('collection_date') ?: now()->toDateString(),
                'expected_qar' => $expected,
                'received_qar' => $received,
                'status' => $received === $expected ? 'matched' : 'variance',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    $rows = DB::table('module_cod_collections')->where('company_id', $companyId)->orderByDesc('collection_date')->get();
    $totalVariance = $rows->sum(fn ($r) => $r->received_qar - $r->expected_qar);
@endphp

<div class="space-y-6" data-qatar-module="cod-reconciliation">
    <div class="flow-panel p-6 flex flex-wrap gap-2">
        <a href="{{ route('modules.show', 'qatar-delivery-dispatch') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Deliveries') }}</a>
        <a href="{{ route('payments.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Payments') }}</a>
    </div>
    <div class="flow-panel p-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50"><p class="text-xs text-slate-500">{{ __('Total variance QAR') }}</p><p class="text-2xl font-bold {{ $totalVariance === 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ number_format($totalVariance) }}</p></div>
    </div>
    <div class="flow-panel p-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_cod">
            <input name="courier_name" required class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900" placeholder="{{ __('Courier') }}">
            <input name="collection_date" type="date" value="{{ now()->toDateString() }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="expected_qar" type="number" placeholder="{{ __('Expected QAR') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="received_qar" type="number" placeholder="{{ __('Received QAR') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <button type="submit" class="sm:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Record collection') }}</button>
        </form>
    </div>
    <div class="flow-panel p-0 overflow-x-auto">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2">{{ __('Date') }}</th><th class="px-4 py-2">{{ __('Courier') }}</th><th class="px-4 py-2">{{ __('Expected') }}</th><th class="px-4 py-2">{{ __('Received') }}</th><th class="px-4 py-2">{{ __('Status') }}</th></tr></thead>
        <tbody>@foreach($rows as $r)<tr class="border-t dark:border-slate-800"><td class="px-4 py-2">{{ $r->collection_date }}</td><td class="px-4 py-2">{{ $r->courier_name }}</td><td class="px-4 py-2">{{ number_format($r->expected_qar) }}</td><td class="px-4 py-2">{{ number_format($r->received_qar) }}</td><td class="px-4 py-2">{{ $r->status }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
