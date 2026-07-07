<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">{{ $module->localizedName() }}</h2>
            @if ($module->version)
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">v{{ $module->version }}</span>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl w-full sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700/80 dark:bg-slate-900/40 dark:ring-white/[0.06]">
                <div class="border-b border-slate-200/80 bg-gradient-to-r from-slate-50 to-white px-6 py-5 dark:border-slate-700/80 dark:from-slate-800/40 dark:to-slate-900/40 sm:px-8">
                    @include('modules.partials.sub-nav', ['modulePages' => $modulePages ?? [], 'currentPage' => $currentPage ?? ''])
                </div>
                <div class="p-6 sm:p-8">
                    @if (($currentPage ?? '') === '')
                        @include('modules.partials.manifest-details', [
                            'module' => $module,
                            'installedSlugs' => \App\Models\InstalledModule::query()
                                ->where('company_id', $module->company_id)
                                ->pluck('slug')
                                ->all(),
                        ])
                    @endif
                    @if (! empty($coreView))
                        @include($coreView, [
                            'module' => $module,
                            'moduleSettings' => $moduleSettings ?? null,
                            'novaSuggestUrl' => $novaSuggestUrl ?? route('assistant.suggest'),
                            'novaAssistantUrl' => $novaAssistantUrl ?? route('assistant.index'),
                        ])
                    @elseif (! empty($viewFile))
                        {!! view()->file($viewFile, [
                            'module' => $module,
                            'moduleSettings' => $moduleSettings ?? null,
                            'novaSuggestUrl' => $novaSuggestUrl ?? route('assistant.suggest'),
                            'novaAssistantUrl' => $novaAssistantUrl ?? route('assistant.index'),
                        ])->render() !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
