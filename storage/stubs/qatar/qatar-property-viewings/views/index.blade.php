@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    $flash = null;

    if (request()->query('module_action') === 'store_viewing') {
        $propertyTitle = trim((string) request()->query('property_title', ''));
        if ($propertyTitle !== '') {
            DB::table('module_property_viewings')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'client_id' => request()->query('client_id') ?: null,
                'property_title' => $propertyTitle,
                'zone' => request()->query('zone') ?: null,
                'scheduled_at' => request()->query('scheduled_at') ?: null,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $flash = __('Viewing scheduled.');
        }
    }

    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(200)->get(['id', 'name']);
    $viewings = DB::table('module_property_viewings')->where('company_id', $companyId)->orderByDesc('scheduled_at')->get();
@endphp

<div class="space-y-6" data-qatar-module="property-viewings">
    @if ($flash)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">{{ $flash }}</div>
    @endif

    <div class="flow-panel p-6">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('calendar.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Calendar') }}</a>
            <a href="{{ route('clients.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Clients') }}</a>
            <a href="{{ route('modules.show', 'qatar-property-listings') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Property listings') }}</a>
        </div>
    </div>

    <div class="flow-panel p-6">
        <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('Schedule viewing') }}</h4>
        <form method="get" action="{{ route('modules.show', $module->slug) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_viewing">
            <div class="sm:col-span-2">
                <input name="property_title" required placeholder="{{ __('Property title') }}" class="block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
            </div>
            <input name="zone" placeholder="{{ __('Zone') }}" class="rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
            <input name="scheduled_at" type="datetime-local" class="rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
            <select name="client_id" class="sm:col-span-2 rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                <option value="">{{ __('Client (optional)') }}</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ __('Save') }}</button>
            </div>
        </form>
    </div>

    <div class="flow-panel p-6" x-data="{ loading: false, suggestion: '', async askNova() {
        this.loading = true;
        const res = await fetch(@js($novaSuggestUrl ?? route('assistant.suggest')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ mode: 'client_email', context: 'Viewing confirmation for Doha property' }) });
        const data = await res.json(); this.suggestion = data.suggestion || ''; this.loading = false;
    }}">
        <button type="button" @click="askNova()" :disabled="loading" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200">Nova — {{ __('Confirmation email') }}</button>
        <p x-show="suggestion" x-cloak class="mt-3 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300" x-text="suggestion"></p>
    </div>

    <div class="flow-panel overflow-hidden p-0">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/50"><tr><th class="px-4 py-2 text-start">{{ __('Property') }}</th><th class="px-4 py-2">{{ __('When') }}</th><th class="px-4 py-2">{{ __('Client') }}</th></tr></thead>
            <tbody>
                @forelse ($viewings as $v)
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="px-4 py-2">{{ $v->property_title }} @if($v->zone)<span class="text-slate-400">· {{ $v->zone }}</span>@endif</td>
                        <td class="px-4 py-2">{{ $v->scheduled_at ? \Illuminate\Support\Carbon::parse($v->scheduled_at)->format('Y-m-d H:i') : '—' }}</td>
                        <td class="px-4 py-2">@if($v->client_id)<a href="{{ route('clients.edit', $v->client_id) }}" class="text-indigo-600">{{ $clients->firstWhere('id', $v->client_id)?->name ?? '—' }}</a>@else — @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-slate-500">{{ __('No viewings yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
