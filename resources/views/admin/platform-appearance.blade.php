<x-admin-layout>
    <x-flow.page-header
        :title="__('Default workspace theme')"
        :description="__('Set colors, font, and optional CSS applied to every company workspace before they override it in Appearance settings. Navigation uses the sidebar layout. Logos remain per-company.')"
    />

    <div class="flow-panel max-w-3xl p-8">
        <form method="POST" action="{{ route('admin.platform-appearance.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="theme_name" :value="__('Theme preset')" />
                <select id="theme_name" name="theme_name" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    @foreach ($presets as $presetKey => $preset)
                        @php($label = is_array($preset) && !empty($preset['label']) ? $preset['label'] : ucfirst(str_replace('_', ' ', (string) $presetKey)))
                        <option value="{{ $presetKey }}" @selected(old('theme_name', $rawTheme['theme_name'] ?? 'default') === $presetKey)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('theme_name')" class="mt-2" />
            </div>

            <div class="flex items-center gap-2">
                <input id="apply_preset_colors" name="apply_preset_colors" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800" checked>
                <label for="apply_preset_colors" class="text-sm text-slate-700 dark:text-slate-300">{{ __('Apply preset colors when saving') }}</label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="primary_color" :value="__('Primary color')" />
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" class="h-10 w-12 rounded-lg border border-slate-300 bg-white p-1" data-sync-hex="#primary_color" />
                        <x-text-input id="primary_color" name="primary_color" type="text" class="block w-full font-mono text-sm" :value="old('primary_color', $rawTheme['primary_color'] ?? '#4f46e5')" required />
                    </div>
                    <x-input-error :messages="$errors->get('primary_color')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="secondary_color" :value="__('Secondary color')" />
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" class="h-10 w-12 rounded-lg border border-slate-300 bg-white p-1" data-sync-hex="#secondary_color" />
                        <x-text-input id="secondary_color" name="secondary_color" type="text" class="block w-full font-mono text-sm" :value="old('secondary_color', $rawTheme['secondary_color'] ?? '#64748b')" required />
                    </div>
                    <x-input-error :messages="$errors->get('secondary_color')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="font_family" :value="__('Font')" />
                <select id="font_family" name="font_family" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    @foreach ($fonts as $font)
                        <option value="{{ $font }}" @selected(old('font_family', $rawTheme['font_family'] ?? 'Figtree') === $font)>{{ $font }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('font_family')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="dark_mode" :value="__('Appearance mode')" />
                <select id="dark_mode" name="dark_mode" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                    <option value="light" @selected(old('dark_mode', $rawTheme['dark_mode'] ?? 'system') === 'light')>{{ __('Light') }}</option>
                    <option value="dark" @selected(old('dark_mode', $rawTheme['dark_mode'] ?? 'system') === 'dark')>{{ __('Dark') }}</option>
                    <option value="system" @selected(old('dark_mode', $rawTheme['dark_mode'] ?? 'system') === 'system')>{{ __('System') }}</option>
                </select>
                <x-input-error :messages="$errors->get('dark_mode')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="custom_css" :value="__('Custom CSS (advanced)')" />
                <textarea id="custom_css" name="custom_css" rows="6" class="mt-1 block w-full rounded-lg border-slate-300 font-mono text-xs shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">{{ old('custom_css', $rawTheme['custom_css'] ?? '') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">{{ __('Applied as a baseline; companies can add their own CSS in settings.') }}</p>
                <x-input-error :messages="$errors->get('custom_css')" class="mt-2" />
            </div>

            <x-primary-button type="submit">{{ __('Save defaults') }}</x-primary-button>
        </form>
    </div>
</x-admin-layout>

