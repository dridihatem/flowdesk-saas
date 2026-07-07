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

<div class="space-y-8" data-qatar-module="property-listings">
    @include('modules.partials.flash', ['message' => $flash])

    @include('modules.partials.crm-shortcuts')

    <div class="flow-panel p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'add_listing') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ module_trans($module, 'hub_listings_blurb') }}</p>
        <form method="get" action="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'listings']) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
            <input type="hidden" name="module_action" value="store_listing">
            <div class="sm:col-span-2">
                <x-input-label for="listing_title" :value="__('Title')" />
                <x-text-input id="listing_title" name="title" type="text" class="mt-1 block w-full" required :placeholder="module_trans($module, 'title_placeholder')" />
            </div>
            <div>
                <x-input-label for="zone_id" :value="module_trans($module, 'zone')" />
                <select id="zone_id" name="zone_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="client_id" :value="module_trans($module, 'owner_client')" />
                <select id="client_id" name="client_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="project_id" :value="module_trans($module, 'linked_project')" />
                <select id="project_id" name="project_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="listing_type" :value="__('Type')" />
                <select id="listing_type" name="listing_type" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="sale">{{ __('Sale') }}</option>
                    <option value="rent">{{ __('Rent') }}</option>
                </select>
            </div>
            <div>
                <x-input-label for="price_qar" :value="module_trans($module, 'price_qar')" />
                <x-text-input id="price_qar" name="price_qar" type="number" min="0" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="area_sqm" :value="module_trans($module, 'area_sqm')" />
                <x-text-input id="area_sqm" name="area_sqm" type="number" min="0" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="furnished" :value="__('Furnished')" />
                <select id="furnished" name="furnished" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">—</option>
                    <option value="furnished">{{ __('Furnished') }}</option>
                    <option value="semi">{{ module_trans($module, 'semi_furnished') }}</option>
                    <option value="unfurnished">{{ module_trans($module, 'unfurnished') }}</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"></textarea>
            </div>
            <div class="sm:col-span-2">
                <x-primary-button type="submit" class="!normal-case inline-flex items-center gap-2">
                    <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                    {{ module_trans($module, 'save_listing') }}
                </x-primary-button>
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
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    <i class="fa-solid fa-wand-magic-sparkles me-1 text-indigo-500" aria-hidden="true"></i>
                    Nova
                </h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $module->manifest['ai']['label'] ?? '' }}</p>
            </div>
            <a href="{{ $novaAssistantUrl ?? route('assistant.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Open Nova chat') }}</a>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" @click="askNova('summary', 'Qatar property portfolio summary for workspace')" :disabled="loading" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                {{ module_trans($module, 'portfolio_summary') }}
            </button>
            <button type="button" @click="askNova('client_email', 'Draft a viewing invitation email for a Doha property listing')" :disabled="loading" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                {{ module_trans($module, 'viewing_email') }}
            </button>
        </div>
        <p x-show="loading" x-cloak class="mt-3 text-sm text-slate-500">{{ __('Thinking…') }}</p>
        <div x-show="suggestion" x-cloak class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm whitespace-pre-wrap text-slate-800 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200" x-text="suggestion"></div>
    </div>

    <div class="flow-panel overflow-hidden p-0">
        <div class="border-b border-slate-200/80 px-6 py-4 dark:border-slate-700">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                {{ module_trans($module, 'listings') }}
                <span class="text-sm font-normal text-slate-500">({{ $listings->count() }})</span>
            </h3>
        </div>
        @if ($listings->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">{{ module_trans($module, 'no_listings') }}</p>
        @else
            <x-flow.table>
                <thead class="bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Title') }}</th>
                        <th class="px-4 py-3">{{ __('Zone') }}</th>
                        <th class="px-4 py-3">{{ module_trans($module, 'qar_column') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('CRM') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 text-slate-800 dark:divide-slate-700/80 dark:text-slate-100">
                    @foreach ($listings as $listing)
                        @php
                            $owner = $listing->client_id ? $clients->firstWhere('id', $listing->client_id) : null;
                        @endphp
                        <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-medium">{{ $listing->title }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400">{{ $listing->zone_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm tabular-nums">{{ number_format($listing->price_qar) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium uppercase text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $listing->status }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    @if ($owner)
                                        <a href="{{ route('clients.edit', $owner->id) }}" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ $owner->name }}</a>
                                    @endif
                                    @if ($listing->project_id)
                                        <a href="{{ route('projects.show', $listing->project_id) }}" class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Project') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-flow.table>
        @endif
    </div>
</div>
