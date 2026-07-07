<div class="space-y-8" data-qatar-module="real-estate-hub">
    <div class="flow-panel p-6 sm:p-8">
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ module_trans($module, 'hub_intro') }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'listings']) }}" class="flow-panel block p-6 transition hover:ring-2 hover:ring-indigo-200 dark:hover:ring-indigo-800">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                <i class="fa-solid fa-building" aria-hidden="true"></i>
            </span>
            <h4 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'nav_listings') }}</h4>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ module_trans($module, 'hub_listings_blurb') }}</p>
        </a>
        <a href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'viewings']) }}" class="flow-panel block p-6 transition hover:ring-2 hover:ring-indigo-200 dark:hover:ring-indigo-800">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
            </span>
            <h4 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'nav_viewings') }}</h4>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ module_trans($module, 'hub_viewings_blurb') }}</p>
        </a>
        <a href="{{ route('modules.show', ['slug' => $module->slug, 'page' => 'commissions']) }}" class="flow-panel block p-6 transition hover:ring-2 hover:ring-indigo-200 dark:hover:ring-indigo-800">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                <i class="fa-solid fa-handshake" aria-hidden="true"></i>
            </span>
            <h4 class="mt-4 font-semibold text-slate-900 dark:text-white">{{ module_trans($module, 'nav_commissions') }}</h4>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ module_trans($module, 'hub_commissions_blurb') }}</p>
        </a>
    </div>

    @include('modules.partials.crm-shortcuts')
</div>
