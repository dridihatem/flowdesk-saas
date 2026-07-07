<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ __('settings_modules_title') }}</h2>
    </x-slot>

    <div class="py-10">
        <div
            class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-6"
            @if ($modules->isNotEmpty() || $purchasedModules->isNotEmpty())
                x-data="{
                    query: '',
                    matches(text) {
                        const q = this.query.trim().toLowerCase();
                        if (!q) return true;
                        return String(text || '').toLowerCase().includes(q);
                    },
                    sectionHasVisible(refName) {
                        if (!this.query.trim()) return true;
                        const root = this.$refs[refName];
                        if (!root) return false;
                        return [...root.querySelectorAll('[data-module-search-row]')].some((el) => el.offsetParent !== null);
                    },
                }"
            @endif
        >
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/40 dark:bg-rose-950/50 dark:text-rose-100">
                    {{ session('error') }}
                </div>
            @endif

            @if ($modules->isNotEmpty() || $purchasedModules->isNotEmpty())
                <div class="relative">
                    <label for="module-search" class="sr-only">{{ __('settings_modules_search_label') }}</label>
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                    <input
                        id="module-search"
                        type="search"
                        x-model="query"
                        placeholder="{{ __('settings_modules_search_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-10 pe-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                        autocomplete="off"
                    />
                </div>
            @endif

            @if ($canManage)
                <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('settings_modules_upload_heading') }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('settings_modules_upload_help') }}</p>

                    <details class="mt-4 rounded-lg border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-600/50 dark:bg-slate-800/30">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('settings_modules_zip_structure') }}</summary>
                        <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100"><code>my-module/
├── module.json
├── views/
│   ├── index.blade.php
│   ├── show.blade.php
│   └── reports/
│       └── index.blade.php
├── database/
│   └── migrations/
│       └── 2026_01_01_000001_create_module_….php
├── lang/
│   ├── en.json
│   ├── fr.json
│   └── …
└── assets/
    └── module.js</code></pre>
                        <p class="mt-3 text-xs text-slate-600 dark:text-slate-400">{{ __('settings_modules_manifest_hint') }}</p>
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ __('settings_modules_lang_hint') }}</p>
                    </details>

                    <form method="post" action="{{ route('settings.modules.install') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="module_zip" :value="__('settings_modules_zip_label')" />
                            <input
                                id="module_zip"
                                name="module_zip"
                                type="file"
                                accept=".zip,application/zip"
                                required
                                class="mt-1 block w-full text-sm text-slate-600 file:me-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-slate-300 dark:file:bg-indigo-950/50 dark:file:text-indigo-200"
                            />
                            <x-input-error :messages="$errors->get('module_zip')" class="mt-2" />
                        </div>
                        <x-primary-button>{{ __('settings_modules_install_button') }}</x-primary-button>
                    </form>
                </div>
            @endif

            <div class="rounded-2xl border border-indigo-200/80 bg-indigo-50/50 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-indigo-100 backdrop-blur-sm dark:border-indigo-900/40 dark:bg-indigo-950/20 dark:ring-indigo-900/30">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('settings_modules_purchased_heading') }}</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('settings_modules_purchased_help') }}</p>

                @if ($purchasedModules->isEmpty())
                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('settings_modules_purchased_empty') }}</p>
                @else
                    <ul x-ref="purchasedModules" class="mt-4 divide-y divide-indigo-200/60 dark:divide-indigo-900/40">
                        @foreach ($purchasedModules as $purchase)
                            @php
                                $purchaseSearch = strtolower(implode(' ', array_filter([
                                    $purchase['item']->module_name ?? '',
                                    $purchase['order']->order_number ?? '',
                                    $purchase['module']?->slug ?? '',
                                    $purchase['module']?->name ?? '',
                                ])));
                            @endphp
                            <li
                                data-module-search-row
                                x-show="matches(@js($purchaseSearch))"
                                x-cloak
                                class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-slate-900 dark:text-white">{{ $purchase['item']->module_name }}</p>
                                        @if ($purchase['is_installed'])
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                                {{ __('settings_modules_purchased_installed_badge') }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('Order') }} {{ $purchase['order']->order_number }}
                                        @if ($purchase['order']->paid_at)
                                            · {{ $purchase['order']->paid_at->translatedFormat('j M Y') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($purchase['module']?->zip_path)
                                        <a
                                            href="{{ route('settings.modules.purchased.download', $purchase['module']) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                        >
                                            <i class="fa-solid fa-download text-[10px] text-indigo-500" aria-hidden="true"></i>
                                            {{ __('Download') }}
                                        </a>
                                        @if ($canManage && ! $purchase['is_installed'])
                                            <form method="post" action="{{ route('settings.modules.purchased.install', $purchase['module']) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">
                                                    <i class="fa-solid fa-puzzle-piece text-[10px]" aria-hidden="true"></i>
                                                    {{ __('settings_modules_purchased_install') }}
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-500">{{ __('marketing_checkout_no_zip') }}</span>
                                    @endif
                                    @if ($canManage)
                                        <form
                                            method="post"
                                            action="{{ route('settings.modules.purchased.destroy', $purchase['item']) }}"
                                            onsubmit="return confirm(@json($purchase['is_installed'] ? __('settings_modules_purchased_remove_installed_confirm') : __('settings_modules_purchased_remove_confirm')))"
                                        >
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-300">
                                                <i class="fa-solid fa-trash text-[10px]" aria-hidden="true"></i>
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p
                        x-cloak
                        x-show="query.trim() && !sectionHasVisible('purchasedModules')"
                        class="mt-4 text-sm text-slate-600 dark:text-slate-400"
                    >{{ __('settings_modules_search_empty') }}</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-8 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 dark:ring-white/10">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('settings_modules_installed_heading') }}</h3>

                @if ($modules->isEmpty())
                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">{{ __('settings_modules_empty') }}</p>
                @else
                    <ul x-ref="installedModules" class="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach ($modules as $mod)
                            @php
                                $integrations = collect($mod->manifest['integrations'] ?? [])->filter()->keys()->take(6);
                                $aiModes = collect($mod->manifest['ai']['modes'] ?? [])->filter()->take(4);
                                $moduleSearch = strtolower(implode(' ', array_filter([
                                    $mod->name,
                                    $mod->slug,
                                    $mod->version,
                                    $mod->author,
                                    $mod->description,
                                    $integrations->implode(' '),
                                    $aiModes->implode(' '),
                                ])));
                            @endphp
                            <li
                                data-module-search-row
                                x-show="matches(@js($moduleSearch))"
                                x-cloak
                                class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ $mod->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $mod->slug }} · v{{ $mod->version }}
                                        @if ($mod->author)
                                            · {{ $mod->author }}
                                        @endif
                                    </p>
                                    @if ($mod->description)
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $mod->description }}</p>
                                    @endif
                                    @if ($mod->isBundle())
                                        <p class="mt-2">
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200">{{ __('module_bundle_badge') }}</span>
                                        </p>
                                    @elseif ($partOf = $mod->partOfBundle())
                                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('module_part_of_bundle', ['name' => $partOf['name']]) }}
                                        </p>
                                    @endif
                                    @php
                                        $includesMods = $mod->includesModules();
                                        $relatedMods = $mod->relatedModules();
                                    @endphp
                                    @if ($includesMods !== [])
                                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                                            <span class="font-semibold">{{ __('module_includes_heading') }}:</span>
                                            {{ collect($includesMods)->pluck('name')->implode(', ') }}
                                        </p>
                                    @endif
                                    @if ($relatedMods !== [])
                                        <ul class="mt-2 space-y-1 text-xs text-slate-600 dark:text-slate-400">
                                            @foreach ($relatedMods as $rel)
                                                <li class="flex flex-wrap items-center gap-1">
                                                    <span class="font-medium">{{ $rel['name'] }}</span>
                                                    @if ($rel['required'])
                                                        <span class="text-rose-600 dark:text-rose-400">({{ __('Required') }})</span>
                                                    @elseif ($rel['paid'])
                                                        <span class="text-amber-600 dark:text-amber-400">({{ __('Paid') }}@if($rel['price_hint']) · {{ $rel['price_hint'] }}@endif)</span>
                                                    @else
                                                        <span class="text-slate-400">({{ __('Optional') }})</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($integrations->isNotEmpty())
                                        <p class="mt-2 flex flex-wrap gap-1">
                                            @foreach ($integrations as $key)
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $key }}</span>
                                            @endforeach
                                        </p>
                                    @endif
                                    @if ($aiModes->isNotEmpty())
                                        <p class="mt-1 text-[11px] text-indigo-600 dark:text-indigo-400">
                                            <i class="fa-solid fa-wand-magic-sparkles me-1 text-[10px]" aria-hidden="true"></i>
                                            Nova: {{ $aiModes->implode(', ') }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($mod->is_enabled)
                                        <a
                                            href="{{ route('modules.show', $mod->slug) }}"
                                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                                        >
                                            {{ __('settings_modules_open') }}
                                        </a>
                                    @endif
                                    <form method="post" action="{{ route('settings.modules.toggle', $mod) }}">
                                        @csrf
                                        @method('patch')
                                        <input type="hidden" name="enabled" value="{{ $mod->is_enabled ? '0' : '1' }}">
                                        <button type="submit" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                                            {{ $mod->is_enabled ? __('settings_modules_disable') : __('settings_modules_enable') }}
                                        </button>
                                    </form>
                                    @if ($canManage)
                                        <form method="post" action="{{ route('settings.modules.destroy', $mod) }}" onsubmit="return confirm(@json(__('settings_modules_uninstall_confirm')))">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:border-rose-900/40 dark:bg-rose-950/40 dark:text-rose-300">
                                                {{ __('settings_modules_uninstall') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p
                        x-cloak
                        x-show="query.trim() && !sectionHasVisible('installedModules')"
                        class="mt-4 text-sm text-slate-600 dark:text-slate-400"
                    >{{ __('settings_modules_search_empty') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
