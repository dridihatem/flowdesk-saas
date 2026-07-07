<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Appearance & theme') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl w-full sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-flow border border-flow-border bg-flow-surface p-6 shadow-sm">
                <h3 class="text-lg font-medium text-flow-text">{{ __('Customize workspace') }}</h3>
                <p class="mt-1 text-sm text-flow-text-muted">{{ __('Save to apply your theme across the dashboard.') }}</p>

                <form method="POST" action="{{ route('settings.appearance.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="theme_name" :value="__('Theme templates')" />
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Pick a template, then optionally override only colors and light/dark.') }}</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($presets as $key => $preset)
                                @php
                                    $selected = old('theme_name', $rawTheme['theme_name'] ?? 'default') === $key;
                                    $primary = $preset['primary_color'] ?? '#2563eb';
                                    $secondary = $preset['secondary_color'] ?? '#64748b';
                                    $bg = $preset['background_color'] ?? '#ffffff';
                                    $label = $preset['label'] ?? ucfirst(str_replace('_', ' ', $key));
                                @endphp
                                <label
                                    class="group relative cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                    data-flowdesk-preset-primary="{{ $primary }}"
                                    data-flowdesk-preset-secondary="{{ $secondary }}"
                                >
                                    <input type="radio" name="theme_name" value="{{ $key }}" class="sr-only" @checked($selected) />
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $label }}</div>
                                            <div class="mt-1 font-mono text-[11px] text-gray-500">{{ $key }}</div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-4 w-4 rounded-full" style="background: {{ $primary }};"></span>
                                            <span class="inline-flex h-4 w-4 rounded-full" style="background: {{ $secondary }};"></span>
                                        </div>
                                    </div>

                                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-950/40">
                                        <div class="h-20 w-full rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900" style="background: {{ $bg }};">
                                            <div class="flex items-center justify-between px-3 py-2">
                                                <div class="h-2.5 w-20 rounded-full" style="background: {{ $secondary }}20;"></div>
                                                <div class="h-7 w-16 rounded-md" style="background: {{ $primary }};"></div>
                                            </div>
                                            <div class="px-3">
                                                <div class="mt-2 h-2 w-28 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                                                <div class="mt-2 h-2 w-20 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pointer-events-none absolute inset-0 rounded-xl ring-2 ring-indigo-500/0 transition group-has-[:checked]:ring-indigo-500/60"></div>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('theme_name')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="apply_preset_colors" name="apply_preset_colors" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800" checked>
                        <label for="apply_preset_colors" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Apply preset colors when saving') }}</label>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="primary_color" :value="__('Primary color')" />
                            <div class="mt-1 flex items-center gap-2">
                                <input type="color" class="h-10 w-12 rounded-md border border-gray-300 bg-white p-1 dark:border-gray-600 dark:bg-gray-800" data-sync-hex="#primary_color" />
                                <x-text-input id="primary_color" name="primary_color" type="text" class="block w-full font-mono text-sm" :value="old('primary_color', $rawTheme['primary_color'] ?? '#2563eb')" required />
                            </div>
                            <x-input-error :messages="$errors->get('primary_color')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="secondary_color" :value="__('Secondary color')" />
                            <div class="mt-1 flex items-center gap-2">
                                <input type="color" class="h-10 w-12 rounded-md border border-gray-300 bg-white p-1 dark:border-gray-600 dark:bg-gray-800" data-sync-hex="#secondary_color" />
                                <x-text-input id="secondary_color" name="secondary_color" type="text" class="block w-full font-mono text-sm" :value="old('secondary_color', $rawTheme['secondary_color'] ?? '#64748b')" required />
                            </div>
                            <x-input-error :messages="$errors->get('secondary_color')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="font_family" :value="__('Font')" />
                        <select id="font_family" name="font_family" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            @foreach ($fonts as $font)
                                <option value="{{ $font }}" @selected(old('font_family', $rawTheme['font_family'] ?? 'Figtree') === $font)>{{ $font }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('font_family')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="dark_mode" :value="__('Appearance mode')" />
                        <select id="dark_mode" name="dark_mode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            <option value="light" @selected(old('dark_mode', $rawTheme['dark_mode'] ?? 'system') === 'light')>{{ __('Light') }}</option>
                            <option value="dark" @selected(old('dark_mode', $rawTheme['dark_mode'] ?? 'system') === 'dark')>{{ __('Dark') }}</option>
                            <option value="system" @selected(old('dark_mode', $rawTheme['dark_mode'] ?? 'system') === 'system')>{{ __('System') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('dark_mode')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="logo" :value="__('Logo')" />
                        @if (!empty($theme['logo_url']))
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Current logo') }}:</p>
                            <img src="{{ $theme['logo_url'] }}" alt="" class="mt-2 h-12 w-auto object-contain" />
                            <div class="mt-2 flex items-center gap-2">
                                <input id="remove_logo" name="remove_logo" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800">
                                <label for="remove_logo" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Remove logo') }}</label>
                            </div>
                        @endif
                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="mt-2 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 dark:file:bg-indigo-900/40 dark:file:text-indigo-100" />
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="signature" :value="__('Company signature (invoice PDF)')" />
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Upload a PNG or scan of your stamp; it appears on generated invoice PDFs.') }}</p>
                        @if (!empty($theme['signature_url']))
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Current signature') }}:</p>
                            <img src="{{ $theme['signature_url'] }}" alt="" class="mt-2 max-h-24 w-auto object-contain border border-gray-200 dark:border-gray-600 rounded bg-white" />
                            <div class="mt-2 flex items-center gap-2">
                                <input id="remove_signature" name="remove_signature" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800">
                                <label for="remove_signature" class="text-sm text-gray-700 dark:text-gray-300">{{ __('Remove signature') }}</label>
                            </div>
                        @endif
                        <input id="signature" name="signature" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="mt-2 block w-full text-sm text-gray-600 dark:text-gray-300 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 dark:file:bg-indigo-900/40 dark:file:text-indigo-100" />
                        <x-input-error :messages="$errors->get('signature')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="custom_css" :value="__('Custom CSS (advanced)')" />
                        <textarea id="custom_css" name="custom_css" rows="6" class="mt-1 block w-full rounded-md border-gray-300 font-mono text-xs shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder=":root { }">{{ old('custom_css', $rawTheme['custom_css'] ?? '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Only trusted admins should add CSS.') }}</p>
                        <x-input-error :messages="$errors->get('custom_css')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

