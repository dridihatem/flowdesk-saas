<div class="flow-panel p-6 sm:p-8">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('Hello from your CRM module') }}</h3>
    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">
        {{ __('This page was loaded from an installed .zip module. Edit views/index.blade.php in your package to build your feature.') }}
    </p>
    <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
        Module: <strong>{{ $module->name }}</strong> ({{ $module->slug }}) · v{{ $module->version }}
    </p>
</div>
