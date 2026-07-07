@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    $companyId = auth()->user()->company_id;
    $flash = null;

    $defaultZones = ['The Pearl', 'Lusail', 'West Bay', 'Al Sadd', 'Al Wakrah', 'Al Rayyan', 'Msheireb'];

    if (DB::table('module_property_zones')->where('company_id', $companyId)->count() === 0) {
        foreach ($defaultZones as $i => $zoneName) {
            DB::table('module_property_zones')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'name' => $zoneName,
                'sort' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    if (request()->query('module_action') === 'store_listing') {
        $title = trim((string) request()->query('title', ''));
        if ($title !== '') {
            DB::table('module_property_listings')->insert([
                'id' => (string) Str::ulid(),
                'company_id' => $companyId,
                'client_id' => request()->query('client_id') ?: null,
                'project_id' => request()->query('project_id') ?: null,
                'zone_id' => request()->query('zone_id') ?: null,
                'title' => $title,
                'listing_type' => in_array(request()->query('listing_type'), ['sale', 'rent'], true) ? request()->query('listing_type') : 'sale',
                'status' => in_array(request()->query('status'), ['available', 'reserved', 'sold', 'rented'], true) ? request()->query('status') : 'available',
                'furnished' => request()->query('furnished') ?: null,
                'price_qar' => max(0, (int) request()->query('price_qar', 0)),
                'area_sqm' => request()->query('area_sqm') ? (int) request()->query('area_sqm') : null,
                'description' => request()->query('description') ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $flash = module_trans($module, 'listing_saved');
        }
    }

    $zones = DB::table('module_property_zones')->where('company_id', $companyId)->orderBy('sort')->get();
    $clients = DB::table('clients')->where('company_id', $companyId)->orderBy('name')->limit(200)->get(['id', 'name']);
    $projects = DB::table('projects')->where('company_id', $companyId)->whereNull('deleted_at')->orderByDesc('created_at')->limit(100)->get(['id', 'title']);
    $listings = DB::table('module_property_listings as l')
        ->leftJoin('module_property_zones as z', 'z.id', '=', 'l.zone_id')
        ->where('l.company_id', $companyId)
        ->orderByDesc('l.created_at')
        ->get(['l.*', 'z.name as zone_name']);
@endphp

<div class="space-y-6" data-qatar-module="property-listings">
    @if ($flash)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/40 dark:text-emerald-100">
            {{ $flash }}
        </div>
    @endif

    <div class="flow-panel p-6 sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $module->localizedName() }}</h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $module->localizedDescription() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('clients.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    <i class="fa-solid fa-user-plus text-[10px] text-indigo-500" aria-hidden="true"></i>
                    {{ __('New client') }}
                </a>
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    <i class="fa-solid fa-diagram-project text-[10px] text-indigo-500" aria-hidden="true"></i>
                    {{ __('New project') }}
                </a>
                <a href="{{ route('invoices.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    <i class="fa-solid fa-file-invoice text-[10px] text-indigo-500" aria-hidden="true"></i>
                    {{ __('New invoice') }}
                </a>
                <a href="{{ route('calendar.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    <i class="fa-solid fa-calendar text-[10px] text-indigo-500" aria-hidden="true"></i>
                    {{ __('Calendar') }}
                </a>
            </div>
        </div>
    </div>

    <div class="flow-panel p-6 sm:p-8">
        <h4 class="text-base font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'add_listing') }}</h4>
        <form method="get" action="{{ route('modules.show', $module->slug) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_listing">
            <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Title') }}</label>
                <input name="title" required class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100" placeholder="{{ module_trans($module, 'title_placeholder') }}">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ module_trans($module, 'zone') }}</label>
                <select name="zone_id" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ module_trans($module, 'owner_client') }}</label>
                <select name="client_id" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ module_trans($module, 'linked_project') }}</label>
                <select name="project_id" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Type') }}</label>
                <select name="listing_type" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="sale">{{ __('Sale') }}</option>
                    <option value="rent">{{ __('Rent') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ module_trans($module, 'price_qar') }}</label>
                <input name="price_qar" type="number" min="0" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ module_trans($module, 'area_sqm') }}</label>
                <input name="area_sqm" type="number" min="0" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Furnished') }}</label>
                <select name="furnished" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    <option value="furnished">{{ __('Furnished') }}</option>
                    <option value="semi">{{ module_trans($module, 'semi_furnished') }}</option>
                    <option value="unfurnished">{{ module_trans($module, 'unfurnished') }}</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Description') }}</label>
                <textarea name="description" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"></textarea>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                    {{ module_trans($module, 'save_listing') }}
                </button>
            </div>
        </form>
    </div>

    <div
        class="flow-panel p-6 sm:p-8"
        x-data="{
            loading: false,
            suggestion: '',
            async askNova(mode, context) {
                this.loading = true;
                this.suggestion = '';
                try {
                    const res = await fetch(@js($novaSuggestUrl ?? route('assistant.suggest')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({ mode, context }),
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Nova error');
                    this.suggestion = data.suggestion || '';
                } catch (e) {
                    this.suggestion = e.message || 'Error';
                } finally {
                    this.loading = false;
                }
            }
        }"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="text-base font-semibold text-slate-900 dark:text-white">
                    <i class="fa-solid fa-wand-magic-sparkles me-1 text-indigo-500" aria-hidden="true"></i>
                    Nova
                </h4>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $module->manifest['ai']['label'] ?? '' }}</p>
            </div>
            <a href="{{ $novaAssistantUrl ?? route('assistant.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Open Nova chat') }}</a>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" @click="askNova('summary', 'Qatar property portfolio summary for workspace')" :disabled="loading" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 disabled:opacity-50 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">
                {{ module_trans($module, 'portfolio_summary') }}
            </button>
            <button type="button" @click="askNova('client_email', 'Draft a viewing invitation email for a Doha property listing')" :disabled="loading" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 disabled:opacity-50 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">
                {{ module_trans($module, 'viewing_email') }}
            </button>
        </div>
        <p x-show="loading" x-cloak class="mt-3 text-sm text-slate-500">{{ __('Thinking…') }}</p>
        <div x-show="suggestion" x-cloak class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm whitespace-pre-wrap text-slate-800 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200" x-text="suggestion"></div>
    </div>

    <div class="flow-panel overflow-hidden p-0">
        <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700">
            <h4 class="text-base font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'listings') }} <span class="text-sm font-normal text-slate-500">({{ $listings->count() }})</span></h4>
        </div>
        @if ($listings->isEmpty())
            <p class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400">{{ module_trans($module, 'no_listings') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Title') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Zone') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ module_trans($module, 'qar_column') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase text-slate-500">{{ __('CRM') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($listings as $listing)
                            @php
                                $owner = $listing->client_id
                                    ? $clients->firstWhere('id', $listing->client_id)
                                    : null;
                            @endphp
                            <tr class="bg-white dark:bg-slate-900/40">
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $listing->title }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $listing->zone_name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ number_format($listing->price_qar) }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase dark:bg-slate-800">{{ $listing->status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($owner)
                                            <a href="{{ route('clients.edit', $owner->id) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">{{ $owner->name }}</a>
                                        @endif
                                        @if ($listing->project_id)
                                            <a href="{{ route('projects.show', $listing->project_id) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">{{ __('Project') }}</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
