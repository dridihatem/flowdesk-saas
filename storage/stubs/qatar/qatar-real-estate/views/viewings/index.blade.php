@php
    use Illuminate\Support\Facades\DB;

    $companyId = auth()->user()->company_id;
    $company = auth()->user()->company;
    $settingsService = $moduleSettings ?? app(\App\Services\ModuleSettingsService::class);
    $calendarSyncEnabled = $company && $settingsService->isIntegrationEnabled($module, $company, 'calendar');
    $editId = request()->query('edit');
    $editing = null;

    if ($editId) {
        $editing = DB::table('module_property_viewings')
            ->where('company_id', $companyId)
            ->where('id', $editId)
            ->first();
    }

    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(200)->get(['id', 'name']);
    $viewings = DB::table('module_property_viewings')->where('company_id', $companyId)->orderByDesc('scheduled_at')->get();
    $actionsUrl = route('modules.actions', $module->slug);
@endphp

<div class="space-y-8" data-qatar-module="property-viewings">
    @include('modules.partials.flash')
    @include('modules.partials.crm-shortcuts')

    @if (! $calendarSyncEnabled)
        <div class="rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-sm text-slate-600 dark:border-slate-700/80 dark:bg-slate-800/30 dark:text-slate-300">
            <i class="fa-regular fa-calendar me-1" aria-hidden="true"></i>
            {{ module_label($module, 'calendar_sync_off', 'module_calendar_sync_off') }}
            @if (auth()->user()->hasRole('company_admin'))
                <a href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'settings']) }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{{ module_label($module, 'nav_settings', 'module_nav_settings') }}</a>
            @endif
        </div>
    @endif

    <div class="flow-panel p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
            {{ $editing ? module_trans($module, 'edit_viewing') : module_trans($module, 'schedule_viewing') }}
        </h3>
        <form method="post" action="{{ $actionsUrl }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            @csrf
            <input type="hidden" name="module_action" value="{{ $editing ? 'update_viewing' : 'store_viewing' }}">
            <input type="hidden" name="return_page" value="viewings">
            @if ($editing)
                <input type="hidden" name="viewing_id" value="{{ $editing->id }}">
            @endif
            <div class="sm:col-span-2">
                <x-input-label for="property_title" :value="__('Property title')" />
                <x-text-input id="property_title" name="property_title" type="text" class="mt-1 block w-full" :value="old('property_title', $editing->property_title ?? '')" required />
            </div>
            <div>
                <x-input-label for="zone" :value="module_trans($module, 'zone')" />
                <x-text-input id="zone" name="zone" type="text" class="mt-1 block w-full" :value="old('zone', $editing->zone ?? '')" />
            </div>
            <div>
                <x-input-label for="scheduled_at" :value="__('When')" />
                @php
                    $scheduledValue = old('scheduled_at');
                    if ($scheduledValue === null && $editing?->scheduled_at) {
                        $scheduledValue = \Illuminate\Support\Carbon::parse($editing->scheduled_at)->format('Y-m-d\TH:i');
                    }
                @endphp
                <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full" :value="$scheduledValue" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="client_id" :value="__('Client (optional)')" />
                <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($clients as $c)
                        <option value="{{ $c->id }}" @selected((string) old('client_id', $editing->client_id ?? '') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 flex flex-wrap gap-3">
                <x-primary-button type="submit" class="!normal-case">{{ $editing ? __('Update') : __('Save') }}</x-primary-button>
                @if ($editing)
                    <a href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'viewings']) }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ __('Cancel') }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="flow-panel p-6 sm:p-8" x-data="{ loading: false, suggestion: '', async askNova() {
        this.loading = true;
        const res = await fetch(@js($novaSuggestUrl ?? route('assistant.suggest')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ mode: 'client_email', context: 'Viewing confirmation for Doha property' }) });
        const data = await res.json(); this.suggestion = data.suggestion || ''; this.loading = false;
    }}">
        <button type="button" @click="askNova()" :disabled="loading" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            Nova — {{ module_trans($module, 'confirmation_email') }}
        </button>
        <p x-show="suggestion" x-cloak class="mt-3 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-300" x-text="suggestion"></p>
    </div>

    <div class="flow-panel overflow-hidden p-0">
        <x-flow.table>
            <thead class="bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Property') }}</th>
                    <th class="px-4 py-3">{{ __('When') }}</th>
                    <th class="px-4 py-3">{{ __('Client') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/80 dark:divide-slate-700/80">
                @forelse ($viewings as $v)
                    <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 font-medium">
                            {{ $v->property_title }}
                            @if($v->zone)<span class="text-slate-400">· {{ $v->zone }}</span>@endif
                            @if($calendarSyncEnabled && filled($v->calendar_event_id ?? null))
                                <span class="ms-1 inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300" title="{{ module_trans($module, 'on_calendar') }}"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $v->scheduled_at ? \Illuminate\Support\Carbon::parse($v->scheduled_at)->format('Y-m-d H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-sm">@if($v->client_id)<a href="{{ route('clients.edit', $v->client_id) }}" class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $clients->firstWhere('id', $v->client_id)?->name ?? '—' }}</a>@else — @endif</td>
                        <td class="px-4 py-3 text-end">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'viewings', 'edit' => $v->id]) }}" class="text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Edit') }}</a>
                                <form method="post" action="{{ $actionsUrl }}" class="inline" onsubmit="return confirm(@json(__('Are you sure?')))">
                                    @csrf
                                    <input type="hidden" name="module_action" value="delete_viewing">
                                    <input type="hidden" name="return_page" value="viewings">
                                    <input type="hidden" name="viewing_id" value="{{ $v->id }}">
                                    <button type="submit" class="text-sm font-semibold text-rose-600 hover:underline dark:text-rose-400">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">{{ module_trans($module, 'no_viewings') }}</td></tr>
                @endforelse
            </tbody>
        </x-flow.table>
    </div>
</div>
