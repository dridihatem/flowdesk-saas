@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    if (request()->query('module_action') === 'store_appointment') {
        $practitioner = trim((string) request()->query('practitioner_name', ''));
        $service = trim((string) request()->query('service_name', ''));
        if ($practitioner !== '' && $service !== '') {
            DB::table('module_appointments')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'client_id' => request()->query('client_id') ?: null,
                'practitioner_name' => $practitioner,
                'service_name' => $service,
                'starts_at' => request()->query('starts_at') ?: null,
                'fee_qar' => max(0, (int) request()->query('fee_qar', 0)),
                'status' => 'booked',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    $rows = DB::table('module_appointments')->where('company_id', $companyId)->orderBy('starts_at')->get();
    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(100)->get(['id', 'name']);
@endphp

<div class="space-y-6" data-qatar-module="appointments">
    <div class="flow-panel p-6 flex flex-wrap gap-2">
        <a href="{{ route('calendar.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Calendar') }}</a>
        <a href="{{ route('invoices.create') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Invoice consultation') }}</a>
        <a href="{{ route('clients.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Clients') }}</a>
    </div>
    <div class="flow-panel p-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_appointment">
            <input name="practitioner_name" required placeholder="{{ __('Practitioner') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="service_name" required placeholder="{{ __('Service') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="starts_at" type="datetime-local" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="fee_qar" type="number" placeholder="QAR" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <select name="client_id" class="sm:col-span-2 rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"><option value="">{{ __('Client') }}</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Book') }}</button>
        </form>
    </div>
    <div class="flow-panel p-0 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2">{{ __('When') }}</th><th class="px-4 py-2">{{ __('Service') }}</th><th class="px-4 py-2">{{ __('Practitioner') }}</th><th class="px-4 py-2">{{ __('Client') }}</th></tr></thead>
            <tbody>@foreach($rows as $r)<tr class="border-t dark:border-slate-800"><td class="px-4 py-2">{{ $r->starts_at ? \Illuminate\Support\Carbon::parse($r->starts_at)->format('M j H:i') : '—' }}</td><td class="px-4 py-2">{{ $r->service_name }}</td><td class="px-4 py-2">{{ $r->practitioner_name }}</td><td class="px-4 py-2">@if($r->client_id)<a href="{{ route('clients.edit', $r->client_id) }}" class="text-indigo-600">{{ $clients->firstWhere('id', $r->client_id)?->name }}</a>@endif</td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
