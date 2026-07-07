<x-app-layout>
    <x-slot name="header">
        <h2 class="flow-font-display text-xl font-semibold leading-tight text-slate-800 dark:text-slate-100">
            {{ __('Company settings') }}
        </h2>
    </x-slot>

    <div
        class="flow-page-shell"
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
                return [...root.querySelectorAll('[data-settings-search-row]')].some((el) => el.offsetParent !== null);
            },
            hasAnyVisible() {
                if (!this.query.trim()) return true;
                return [...this.$el.querySelectorAll('[data-settings-search-row]')].some((el) => el.offsetParent !== null);
            },
        }"
    >
        <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-base font-medium text-slate-800 dark:text-slate-200">{{ __('Workspace settings headline') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Workspace settings intro') }}</p>
                <div class="relative mt-5 max-w-md">
                    <label for="settings-hub-search" class="sr-only">{{ __('settings_hub_search_label') }}</label>
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true"></i>
                    <input
                        id="settings-hub-search"
                        type="search"
                        x-model="query"
                        placeholder="{{ __('settings_hub_search_placeholder') }}"
                        class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-10 pe-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                        autocomplete="off"
                    />
                </div>
            </div>
            {{-- Quick links to groups --}}
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Company settings') }}">
                @foreach ($groups as $group)
                    <a
                        href="#settings-group-{{ $loop->index }}"
                        x-show="sectionHasVisible('settingsGroup{{ $loop->index }}')"
                        class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-indigo-500/50 dark:hover:text-indigo-300"
                    >
                        {{ $group['label'] }}
                        <span class="rounded-full bg-slate-100 px-1.5 text-[10px] font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ count($group['cards']) }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="space-y-8">
            <p
                x-cloak
                x-show="query.trim() && !hasAnyVisible()"
                class="rounded-xl border border-slate-200/80 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400"
            >{{ __('settings_hub_search_empty') }}</p>

            @foreach ($groups as $group)
                <section
                    id="settings-group-{{ $loop->index }}"
                    x-ref="settingsGroup{{ $loop->index }}"
                    x-show="sectionHasVisible('settingsGroup{{ $loop->index }}')"
                    x-cloak
                    aria-labelledby="settings-group-label-{{ $loop->index }}"
                    class="flow-panel scroll-mt-24 overflow-hidden p-0"
                >
                    <div class="flex items-center gap-3 border-b border-slate-200/80 bg-gradient-to-r from-indigo-50/70 via-transparent to-transparent px-5 py-4 dark:border-slate-700/80 dark:from-indigo-950/30">
                        <h2
                            id="settings-group-label-{{ $loop->index }}"
                            class="flow-font-display text-sm font-semibold uppercase tracking-[0.18em] text-slate-600 dark:text-slate-300"
                        >
                            {{ $group['label'] }}
                        </h2>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">{{ count($group['cards']) }}</span>
                    </div>

                    <div class="grid gap-px bg-slate-200/70 dark:bg-slate-700/60 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($group['cards'] as $card)
                            @php
                                $cardSearch = strtolower(implode(' ', array_filter([
                                    $card['title'] ?? '',
                                    $card['summary'] ?? '',
                                    $group['label'] ?? '',
                                ])));
                            @endphp
                            <a
                                href="{{ route($card['route']) }}"
                                data-settings-search-row
                                x-show="matches(@js($cardSearch))"
                                x-cloak
                                class="group flex items-start gap-3 bg-white p-5 transition hover:bg-indigo-50/50 dark:bg-slate-900 dark:hover:bg-indigo-950/30"
                            >
                                <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500/15 to-violet-500/10 text-indigo-600 transition group-hover:from-indigo-500/25 group-hover:to-violet-500/20 dark:from-indigo-400/20 dark:to-violet-400/10 dark:text-indigo-300">
                                    <x-flow.nav-icon :name="$card['icon']" class="!text-[1.05rem]" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $card['title'] }}</span>
                                        <i class="fa-solid fa-arrow-right ms-auto shrink-0 text-xs text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500 dark:text-slate-600" aria-hidden="true"></i>
                                    </span>
                                    <span class="mt-1 block text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $card['summary'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
