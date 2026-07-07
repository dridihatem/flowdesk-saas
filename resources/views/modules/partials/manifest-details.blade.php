@php
    $includes = $module->includesModules();
    $related = $module->relatedModules();
    $partOf = $module->partOfBundle();
    $installedSlugs = $installedSlugs ?? [];
@endphp

@if ($partOf || $includes !== [] || $related !== [])
    <aside class="mb-6 rounded-xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-700/80 dark:bg-slate-800/30">
        @if ($partOf)
            <p class="text-sm text-slate-700 dark:text-slate-300">
                <i class="fa-solid fa-layer-group me-1 text-indigo-500" aria-hidden="true"></i>
                {{ __('module_part_of_bundle', ['name' => $partOf['name']]) }}
                @if (! in_array($partOf['slug'], $installedSlugs, true))
                    <span class="text-slate-500">— {{ __('module_install_bundle_hint') }}</span>
                @endif
            </p>
        @endif

        @if ($includes !== [])
            <div @class(['mt-4' => $partOf])>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('module_includes_heading') }}</h3>
                <ul class="mt-2 space-y-2">
                    @foreach ($includes as $item)
                        <li class="flex flex-wrap items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            <i class="fa-solid fa-check text-emerald-500 text-xs" aria-hidden="true"></i>
                            <span class="font-medium">{{ $item['name'] }}</span>
                            @if ($item['required'])
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">{{ __('Required') }}</span>
                            @endif
                            @if ($item['paid'])
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">{{ __('Paid') }}</span>
                                @if ($item['price_hint'])
                                    <span class="text-xs text-slate-500">{{ $item['price_hint'] }}</span>
                                @endif
                            @else
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">{{ __('Included') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($related !== [])
            <div class="mt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('module_related_heading') }}</h3>
                <ul class="mt-2 space-y-2">
                    @foreach ($related as $item)
                        @php
                            $isInstalled = in_array($item['slug'], $installedSlugs, true);
                        @endphp
                        <li class="flex flex-wrap items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
                            @if ($isInstalled)
                                <i class="fa-solid fa-circle-check text-emerald-500 text-xs" aria-hidden="true"></i>
                            @else
                                <i class="fa-regular fa-circle text-slate-400 text-xs" aria-hidden="true"></i>
                            @endif
                            <span class="font-medium">{{ $item['name'] }}</span>
                            @if ($item['required'])
                                <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">{{ __('Required') }}</span>
                            @endif
                            @if (! empty($item['included']))
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">{{ __('In bundle') }}</span>
                            @elseif ($item['paid'])
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">{{ __('Paid add-on') }}</span>
                                @if ($item['price_hint'])
                                    <span class="text-xs text-slate-500">{{ $item['price_hint'] }}</span>
                                @endif
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ __('Optional') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </aside>
@endif
