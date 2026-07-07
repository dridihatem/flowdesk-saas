@props(['bodyFieldId' => 'body_html'])

@php
    $starterPresets = array_map(static function (array $p): array {
        return [
            'id' => $p['id'],
            'name' => __($p['name_key']),
            'swatch' => $p['swatch'],
            'tokens' => $p['tokens'],
        ];
    }, \App\Support\EmailMarketingStarterLayout::presets());
    $starterBase = \App\Support\EmailMarketingStarterLayout::baseTemplate();
@endphp

<div
    x-data="emailMarketingStarterModal({ fieldId: @js($bodyFieldId), baseTemplate: @js($starterBase), presets: @js($starterPresets) })"
    @keydown.escape.window="open = false"
    class="inline-flex"
>
    <button
        type="button"
        @click="openModal()"
        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-indigo-500/40 dark:bg-indigo-950/30 dark:text-indigo-200 dark:hover:bg-indigo-900/40"
    >
        <i class="fa-solid fa-wand-magic-sparkles text-xs opacity-90" aria-hidden="true"></i>
        <span>{{ __('email_marketing_starter_layout_button') }}</span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[200] flex items-end justify-center bg-slate-900/50 p-3 sm:items-center sm:p-4"
        @click.self="open = false"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('email_marketing_starter_layout_modal_title') }}"
    >
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-600 dark:bg-slate-900 lg:max-h-[90vh] lg:flex-row"
        >
            <div class="flex min-h-0 min-w-0 flex-1 flex-col border-b border-slate-200 dark:border-slate-700 lg:border-b-0 lg:border-r">
                <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5 sm:py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('email_marketing_starter_layout_modal_title') }}</h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __('email_marketing_starter_layout_modal_intro') }}</p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        @click="open = false"
                    >
                        <span class="sr-only">{{ __('email_marketing_starter_layout_close') }}</span>
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-auto bg-slate-100/90 p-2 sm:p-3 dark:bg-slate-800/50">
                    <iframe
                        class="h-[min(52vh,480px)] w-full rounded-lg border border-slate-200 bg-white sm:h-[min(58vh,520px)] dark:border-slate-600"
                        title="{{ __('email_marketing_starter_layout_preview') }}"
                        sandbox="allow-scripts allow-same-origin"
                        x-bind:srcdoc="html"
                    ></iframe>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="open = false"
                    >
                        {{ __('email_marketing_starter_layout_close') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                        @click="apply"
                    >
                        <i class="fa-solid fa-paste text-xs" aria-hidden="true"></i>
                        {{ __('email_marketing_starter_layout_apply') }}
                    </button>
                </div>
            </div>

            <div class="flex w-full shrink-0 flex-col border-t border-slate-200 bg-slate-50/90 dark:border-slate-700 dark:bg-slate-900/80 lg:w-[min(100%,22rem)] lg:border-l lg:border-t-0">
                <div class="shrink-0 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_theme_list') }}</p>
                    <p class="mt-0.5 text-[0.7rem] text-slate-500 dark:text-slate-500">{{ __('email_marketing_starter_drag_hint') }}</p>
                </div>
                <div class="min-h-0 max-h-[32vh] overflow-y-auto overflow-x-hidden px-3 py-2 lg:max-h-none lg:flex-1">
                    <ul
                        x-ref="presetList"
                        class="space-y-2"
                        role="list"
                    >
                        @foreach ($starterPresets as $p)
                            <li
                                data-preset-item
                                class="flowdesk-starter-preset-item group cursor-grab select-none active:cursor-grabbing"
                            >
                                <div
                                    class="flex w-full items-stretch gap-0 overflow-hidden rounded-xl border transition"
                                    :class="selectedId === '{{ $p['id'] }}' ? 'border-indigo-500 ring-2 ring-indigo-500/30 dark:border-indigo-400' : 'border-slate-200 dark:border-slate-600'"
                                >
                                    <span
                                        data-preset-handle
                                        class="flex w-8 shrink-0 items-center justify-center bg-slate-200/80 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                        aria-hidden="true"
                                    >
                                        <i class="fa-solid fa-grip-vertical text-xs"></i>
                                    </span>
                                    <button
                                        type="button"
                                        class="min-w-0 flex-1 px-2 py-2.5 text-left"
                                        @click="selectPreset(@js($p['id']))"
                                    >
                                        <span class="flex items-center gap-2">
                                            @foreach (array_slice($p['swatch'], 0, 3) as $hex)
                                                <span
                                                    class="h-5 w-5 rounded-full border border-slate-200/80 shadow-inner dark:border-slate-600"
                                                    style="background-color: {{ $hex }};"
                                                ></span>
                                            @endforeach
                                        </span>
                                        <span class="mt-1 line-clamp-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $p['name'] }}</span>
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="shrink-0 space-y-3 border-t border-slate-200 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ __('email_marketing_starter_customize') }}</p>
                        <button
                            type="button"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            @click="resetThemeTweaks"
                        >{{ __('email_marketing_starter_reset') }}</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <label class="block">
                            <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_color_accent') }}</span>
                            <input
                                type="color"
                                class="h-9 w-full cursor-pointer rounded-md border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800"
                                :value="colorForRole('accent')"
                                @input="setAccent"
                            />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_color_outer') }}</span>
                            <input
                                type="color"
                                class="h-9 w-full cursor-pointer rounded-md border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800"
                                :value="colorForRole('outer')"
                                @input="setOuterBg"
                            />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_color_card') }}</span>
                            <input
                                type="color"
                                class="h-9 w-full cursor-pointer rounded-md border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800"
                                :value="colorForRole('card')"
                                @input="setCardBg"
                            />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_color_border') }}</span>
                            <input
                                type="color"
                                class="h-9 w-full cursor-pointer rounded-md border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800"
                                :value="colorForRole('border')"
                                @input="setBorderColor"
                            />
                        </label>
                    </div>
                    <label class="block">
                        <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_color_heading') }}</span>
                        <input
                            type="color"
                            class="h-9 w-full max-w-[8rem] cursor-pointer rounded-md border border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-800"
                            :value="colorForRole('heading')"
                            @input="setHeadingColor"
                        />
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_border_weight') }}</span>
                            <select
                                x-model="borderW"
                                class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-8 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option value="0px">{{ __('email_marketing_starter_border_none') }}</option>
                                <option value="1px">1px</option>
                                <option value="2px">2px</option>
                                <option value="3px">3px</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-[0.65rem] font-medium text-slate-500 dark:text-slate-400">{{ __('email_marketing_starter_corner_radius') }}</span>
                            <select
                                x-model="cardR"
                                class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-2 pr-8 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                            >
                                <option value="0px">0</option>
                                <option value="8px">{{ __('email_marketing_starter_radius_tight') }}</option>
                                <option value="12px">12px</option>
                                <option value="16px">{{ __('email_marketing_starter_radius_soft') }}</option>
                                <option value="20px">20px</option>
                                <option value="24px">{{ __('email_marketing_starter_radius_roomy') }}</option>
                            </select>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
