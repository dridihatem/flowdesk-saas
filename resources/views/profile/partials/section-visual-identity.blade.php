@php
    $theme = is_array($profileTheme ?? null) ? $profileTheme : [];
    $logoUrl = $theme['logo_url'] ?? null;
    $signatureUrl = $theme['signature_url'] ?? null;
    $primary = $theme['primary_color'] ?? '#2563eb';
    $secondary = $theme['secondary_color'] ?? '#64748b';
    $themeName = $theme['theme_name'] ?? 'default';
@endphp

<div class="flex flex-col gap-6 lg:flex-row lg:items-start">
    <div class="flex flex-wrap gap-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Logo') }}</p>
            <div class="mt-2 flex h-24 w-40 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-800/50">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ __('Logo') }}" class="max-h-full max-w-full object-contain" />
                @else
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('No logo uploaded') }}</span>
                @endif
            </div>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Signature') }}</p>
            <div class="mt-2 flex h-24 w-40 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 dark:border-slate-600 dark:bg-slate-800/50">
                @if ($signatureUrl)
                    <img src="{{ $signatureUrl }}" alt="{{ __('Signature') }}" class="max-h-full max-w-full object-contain" />
                @else
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('No signature uploaded') }}</span>
                @endif
            </div>
        </div>
    </div>

    <dl class="grid flex-1 gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Theme templates') }}</dt>
            <dd class="mt-0.5 font-mono text-slate-800 dark:text-slate-200">{{ $themeName }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Dark mode') }}</dt>
            <dd class="mt-0.5 text-slate-800 dark:text-slate-200">{{ ! empty($theme['dark_mode']) ? __('On') : __('Off') }}</dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Primary color') }}</dt>
            <dd class="mt-0.5 flex items-center gap-2 text-slate-800 dark:text-slate-200">
                <span class="inline-flex h-4 w-4 rounded-full border border-slate-200 dark:border-slate-600" style="background: {{ $primary }}"></span>
                <span class="font-mono text-xs">{{ $primary }}</span>
            </dd>
        </div>
        <div>
            <dt class="font-medium text-slate-500 dark:text-slate-400">{{ __('Secondary color') }}</dt>
            <dd class="mt-0.5 flex items-center gap-2 text-slate-800 dark:text-slate-200">
                <span class="inline-flex h-4 w-4 rounded-full border border-slate-200 dark:border-slate-600" style="background: {{ $secondary }}"></span>
                <span class="font-mono text-xs">{{ $secondary }}</span>
            </dd>
        </div>
    </dl>
</div>
