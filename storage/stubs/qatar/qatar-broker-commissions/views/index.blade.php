@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;

    if (request()->query('module_action') === 'store_deal') {
        $title = trim((string) request()->query('title', ''));
        if ($title !== '') {
            DB::table('module_deal_splits')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'client_id' => request()->query('client_id') ?: null,
                'provider_id' => request()->query('provider_id') ?: null,
                'title' => $title,
                'deal_amount_qar' => max(0, (int) request()->query('deal_amount_qar', 0)),
                'commission_pct' => min(100, max(0, (int) request()->query('commission_pct', 2))),
                'status' => 'pipeline',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    $deals = DB::table('module_deal_splits')->where('company_id', $companyId)->orderByDesc('created_at')->get();
    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(100)->get(['id', 'name']);
    $providers = DB::table('providers')->where('company_id', $companyId)->orderBy('name')->limit(100)->get(['id', 'name']);
@endphp

<div class="space-y-6" data-qatar-module="broker-commissions">
    <div class="flow-panel p-6 flex flex-wrap gap-2">
        <a href="{{ route('providers.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Providers') }}</a>
        <a href="{{ route('clients.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Clients') }}</a>
        <a href="{{ route('invoices.index') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Invoices') }}</a>
        <a href="{{ route('modules.show', 'qatar-property-listings') }}" class="rounded-lg border px-3 py-1.5 text-xs font-semibold dark:border-slate-600">{{ __('Listings') }}</a>
    </div>

    <div class="flow-panel p-6">
        <form method="get" class="grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_deal">
            <input name="title" required placeholder="{{ __('Deal title') }}" class="sm:col-span-2 rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="deal_amount_qar" type="number" placeholder="{{ __('Deal amount QAR') }}" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <input name="commission_pct" type="number" min="0" max="100" value="2" placeholder="%" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900">
            <select name="client_id" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"><option value="">{{ __('Buyer client') }}</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            <select name="provider_id" class="rounded-lg border text-sm dark:border-slate-600 dark:bg-slate-900"><option value="">{{ __('Agent provider') }}</option>@foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <button type="submit" class="sm:col-span-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Add deal') }}</button>
        </form>
    </div>

    <div class="flow-panel p-0 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2 text-start">{{ __('Deal') }}</th><th class="px-4 py-2">{{ __('QAR') }}</th><th class="px-4 py-2">{{ __('%') }}</th><th class="px-4 py-2">{{ __('Commission') }}</th><th class="px-4 py-2">{{ __('Links') }}</th></tr></thead>
            <tbody>
                @foreach ($deals as $deal)
                    @php $commission = (int) round($deal->deal_amount_qar * $deal->commission_pct / 100); @endphp
                    <tr class="border-t dark:border-slate-800">
                        <td class="px-4 py-2 font-medium">{{ $deal->title }}</td>
                        <td class="px-4 py-2">{{ number_format($deal->deal_amount_qar) }}</td>
                        <td class="px-4 py-2">{{ $deal->commission_pct }}%</td>
                        <td class="px-4 py-2 font-semibold text-emerald-700 dark:text-emerald-400">{{ number_format($commission) }} QAR</td>
                        <td class="px-4 py-2 text-xs">
                            @if($deal->client_id)<a href="{{ route('clients.edit', $deal->client_id) }}" class="text-indigo-600">{{ __('Client') }}</a>@endif
                            @if($deal->provider_id) · <a href="{{ route('providers.edit', $deal->provider_id) }}" class="text-indigo-600">{{ __('Provider') }}</a>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
