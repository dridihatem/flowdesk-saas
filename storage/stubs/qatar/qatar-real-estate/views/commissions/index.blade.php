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

<div class="space-y-8" data-qatar-module="broker-commissions">
    @include('modules.partials.crm-shortcuts')

    <div class="flow-panel p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'add_deal') }}</h3>
        <form method="get" action="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'commissions']) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_deal">
            <div class="sm:col-span-2">
                <x-input-label for="deal_title" :value="module_trans($module, 'deal_title')" />
                <x-text-input id="deal_title" name="title" type="text" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="deal_amount_qar" :value="module_trans($module, 'deal_amount_qar')" />
                <x-text-input id="deal_amount_qar" name="deal_amount_qar" type="number" min="0" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="commission_pct" :value="__('%')" />
                <x-text-input id="commission_pct" name="commission_pct" type="number" min="0" max="100" value="2" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="client_id" :value="module_trans($module, 'buyer_client')" />
                <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <x-input-label for="provider_id" :value="module_trans($module, 'agent_provider')" />
                <select id="provider_id" name="provider_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <x-primary-button type="submit" class="!normal-case">{{ module_trans($module, 'add_deal') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="flow-panel overflow-hidden p-0">
        <x-flow.table>
            <thead class="bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Deal') }}</th>
                    <th class="px-4 py-3">{{ module_trans($module, 'qar_column') }}</th>
                    <th class="px-4 py-3">{{ __('%') }}</th>
                    <th class="px-4 py-3">{{ module_trans($module, 'commission') }}</th>
                    <th class="px-4 py-3">{{ __('Links') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                @forelse ($deals as $deal)
                    @php $commission = (int) round($deal->deal_amount_qar * $deal->commission_pct / 100); @endphp
                    <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 font-medium">{{ $deal->title }}</td>
                        <td class="px-4 py-3 text-sm tabular-nums">{{ number_format($deal->deal_amount_qar) }}</td>
                        <td class="px-4 py-3 text-sm">{{ $deal->commission_pct }}%</td>
                        <td class="px-4 py-3 text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ number_format($commission) }} QAR</td>
                        <td class="px-4 py-3 text-sm">
                            @if($deal->client_id)<a href="{{ route('clients.edit', $deal->client_id) }}" class="font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Client') }}</a>@endif
                            @if($deal->provider_id) · <a href="{{ route('providers.edit', $deal->provider_id) }}" class="font-semibold text-indigo-600 dark:text-indigo-400">{{ __('Provider') }}</a>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">—</td></tr>
                @endforelse
            </tbody>
        </x-flow.table>
    </div>
</div>
