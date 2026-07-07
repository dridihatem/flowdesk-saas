<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ __('settings_navigation_title') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('settings_navigation_intro') }}</p>
            </div>
            <a href="{{ route('settings.workspace') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Company settings') }}</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/50 dark:text-emerald-100">{{ session('status') }}</div>
            @endif

            <form
                method="POST"
                action="{{ route('settings.navigation.update') }}"
                x-data="{
                    sections: @js($sections),
                    moveSection(si, dir) {
                        const target = si + dir;
                        if (target < 0 || target >= this.sections.length) { return; }
                        const [section] = this.sections.splice(si, 1);
                        this.sections.splice(target, 0, section);
                    },
                    moveItem(si, ii, dir) {
                        const items = this.sections[si].items;
                        const target = ii + dir;
                        if (target < 0 || target >= items.length) { return; }
                        const [item] = items.splice(ii, 1);
                        items.splice(target, 0, item);
                    },
                    sectionOrderKeys() {
                        return this.sections.map((s) => s.key);
                    },
                    orderKeys() {
                        return this.sections.flatMap((s) => s.items.map((i) => i.key));
                    },
                    hiddenKeys() {
                        return this.sections.flatMap((s) => s.items.filter((i) => ! i.enabled).map((i) => i.key));
                    },
                }"
            >
                @csrf
                @method('PUT')

                <template x-for="k in sectionOrderKeys()" :key="'s-' + k">
                    <input type="hidden" name="section_order[]" :value="k" />
                </template>
                <template x-for="k in orderKeys()" :key="'o-' + k">
                    <input type="hidden" name="order[]" :value="k" />
                </template>
                <template x-for="k in hiddenKeys()" :key="'h-' + k">
                    <input type="hidden" name="hidden[]" :value="k" />
                </template>

                <div class="space-y-6">
                    <template x-for="(section, si) in sections" :key="section.key">
                        <div class="flow-panel overflow-hidden p-0">
                            <div class="flex items-center gap-3 border-b border-slate-200/80 bg-slate-50/80 px-5 py-3 dark:border-slate-700/80 dark:bg-slate-800/60">
                                <div class="flex shrink-0 flex-col gap-0.5">
                                    <button
                                        type="button"
                                        class="flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 disabled:opacity-30 dark:hover:bg-slate-800"
                                        :disabled="si === 0"
                                        x-on:click="moveSection(si, -1)"
                                        title="{{ __('settings_navigation_move_section_up') }}"
                                    ><i class="fa-solid fa-chevron-up text-xs" aria-hidden="true"></i></button>
                                    <button
                                        type="button"
                                        class="flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 disabled:opacity-30 dark:hover:bg-slate-800"
                                        :disabled="si === sections.length - 1"
                                        x-on:click="moveSection(si, 1)"
                                        title="{{ __('settings_navigation_move_section_down') }}"
                                    ><i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i></button>
                                </div>
                                <h3 class="min-w-0 flex-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400" x-text="section.label"></h3>
                            </div>
                            <ul class="divide-y divide-slate-200/70 dark:divide-slate-700/70">
                                <template x-for="(item, ii) in section.items" :key="item.key">
                                    <li class="flex items-center gap-3 px-5 py-3" :class="item.enabled ? '' : 'opacity-55'">
                                        <div class="flex shrink-0 flex-col gap-0.5">
                                            <button
                                                type="button"
                                                class="flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 disabled:opacity-30 dark:hover:bg-slate-800"
                                                :disabled="ii === 0"
                                                x-on:click="moveItem(si, ii, -1)"
                                                title="{{ __('settings_navigation_move_up') }}"
                                            ><i class="fa-solid fa-chevron-up text-xs" aria-hidden="true"></i></button>
                                            <button
                                                type="button"
                                                class="flex h-6 w-6 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 disabled:opacity-30 dark:hover:bg-slate-800"
                                                :disabled="ii === section.items.length - 1"
                                                x-on:click="moveItem(si, ii, 1)"
                                                title="{{ __('settings_navigation_move_down') }}"
                                            ><i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i></button>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-slate-800 dark:text-slate-100" x-text="item.label"></span>
                                            <p x-show="item.hint" x-text="item.hint" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"></p>
                                        </div>
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="item.enabled ? 'true' : 'false'"
                                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition"
                                            :class="item.enabled ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600'"
                                            x-on:click="item.enabled = ! item.enabled"
                                        >
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="item.enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>

                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">{{ __('settings_navigation_note') }}</p>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('settings.workspace') }}">
                        <x-secondary-button type="button">{{ __('Cancel') }}</x-secondary-button>
                    </a>
                    <x-primary-button type="submit" class="!normal-case">{{ __('Save changes') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
