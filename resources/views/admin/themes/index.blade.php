<x-admin-layout>
    <x-flow.page-header
        :title="__('Theme library')"
        :description="__('Create multiple theme templates. Companies can preview and select a theme, then override colors and light/dark if needed.')"
    />

    <div class="grid gap-6 lg:grid-cols-12">
        <div class="flow-panel lg:col-span-4 p-6">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Add / update theme') }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ __('Key must be unique (lowercase, numbers, underscore).') }}</p>

            <form method="POST" action="{{ route('admin.themes.store') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <x-input-label for="key" :value="__('Key')" />
                    <x-text-input id="key" name="key" class="mt-1 block w-full font-mono" :value="old('key')" :placeholder="__('e.g. modern_red')" required />
                    <x-input-error :messages="$errors->get('key')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="label" :value="__('Label')" />
                    <x-text-input id="label" name="label" class="mt-1 block w-full" :value="old('label')" :placeholder="__('e.g. Modern Red')" required />
                    <x-input-error :messages="$errors->get('label')" class="mt-2" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="primary_color" :value="__('Primary')" />
                        <div class="mt-1 flex items-center gap-2">
                            <input type="color" class="h-10 w-12 rounded-lg border border-slate-300 bg-white p-1" data-sync-hex="#primary_color" />
                            <x-text-input id="primary_color" name="primary_color" class="block w-full font-mono" :value="old('primary_color', '#dc2626')" required />
                        </div>
                        <x-input-error :messages="$errors->get('primary_color')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="secondary_color" :value="__('Secondary')" />
                        <div class="mt-1 flex items-center gap-2">
                            <input type="color" class="h-10 w-12 rounded-lg border border-slate-300 bg-white p-1" data-sync-hex="#secondary_color" />
                            <x-text-input id="secondary_color" name="secondary_color" class="block w-full font-mono" :value="old('secondary_color', '#0f172a')" required />
                        </div>
                        <x-input-error :messages="$errors->get('secondary_color')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="background_color" :value="__('Background (optional)')" />
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" class="h-10 w-12 rounded-lg border border-slate-300 bg-white p-1" data-sync-hex="#background_color" />
                        <x-text-input id="background_color" name="background_color" class="block w-full font-mono" :value="old('background_color')" placeholder="#ffffff" />
                    </div>
                    <x-input-error :messages="$errors->get('background_color')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="font_family" :value="__('Font')" />
                    <select id="font_family" name="font_family" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        @foreach ($fonts as $font)
                            <option value="{{ $font }}" @selected(old('font_family', 'Figtree') === $font)>{{ $font }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('font_family')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="dark_mode" :value="__('Default mode')" />
                    <select id="dark_mode" name="dark_mode" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm">
                        <option value="light" @selected(old('dark_mode', 'light') === 'light')>{{ __('Light') }}</option>
                        <option value="dark" @selected(old('dark_mode', 'light') === 'dark')>{{ __('Dark') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('dark_mode')" class="mt-2" />
                </div>

                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    <i class="fa-regular fa-floppy-disk" aria-hidden="true"></i>
                    <span>{{ __('Save theme') }}</span>
                </button>
            </form>
        </div>

        <div class="lg:col-span-8">
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($library as $key => $t)
                    @php
                        $primary = $t['primary_color'] ?? '#dc2626';
                        $secondary = $t['secondary_color'] ?? '#0f172a';
                        $bg = $t['background_color'] ?? '#ffffff';
                        $label = $t['label'] ?? $key;
                    @endphp
                    <div class="flow-panel overflow-hidden p-0">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ $label }}</div>
                                    <div class="mt-1 font-mono text-[11px] text-slate-500">{{ $key }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.themes.destroy', ['key' => $key]) }}" onsubmit="return confirm(@json(__('Remove this theme?')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-slate-700 shadow-sm transition hover:bg-rose-50 hover:text-rose-700" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 bg-slate-50 p-5">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="h-28 w-full rounded-xl border border-slate-200"
                                     style="background: {{ $bg }};">
                                    <div class="flex items-center justify-between px-3 py-2">
                                        <div class="h-2.5 w-20 rounded-full" style="background: {{ $secondary }}20;"></div>
                                        <div class="h-7 w-16 rounded-lg" style="background: {{ $primary }};"></div>
                                    </div>
                                    <div class="px-3">
                                        <div class="mt-3 h-2 w-40 rounded-full bg-slate-200"></div>
                                        <div class="mt-2 h-2 w-28 rounded-full bg-slate-200"></div>
                                    </div>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                        <span class="text-slate-500">{{ __('Primary') }}</span>
                                        <span class="font-mono text-slate-700">{{ $primary }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                        <span class="text-slate-500">{{ __('Mode') }}</span>
                                        <span class="font-mono text-slate-700">{{ $t['dark_mode'] ?? 'light' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if (empty($library))
                    <div class="flow-panel p-6 sm:col-span-2 xl:col-span-3">
                        <p class="text-sm text-slate-600">{{ __('No themes yet. Add your first template on the left.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>

