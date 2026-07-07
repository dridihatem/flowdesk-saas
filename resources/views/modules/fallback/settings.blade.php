@php
    use App\Services\PlanLimitService;

    $company = auth()->user()->company;
    $settingsService = $moduleSettings ?? app(\App\Services\ModuleSettingsService::class);
    $manifestKeys = $settingsService->manifestIntegrations($module);
    $enabled = $settingsService->enabledIntegrations($module);
    $planLimits = app(PlanLimitService::class);
    $isAdmin = auth()->user()->hasRole('company_admin');
@endphp

<div class="space-y-8" data-module-settings>
    @include('modules.partials.flash')

    <div class="flow-panel p-6 sm:p-8">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ module_label($module, 'nav_settings', 'module_nav_settings') }}</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ module_label($module, 'settings_intro', 'module_settings_intro') }}</p>

        @if (! $isAdmin)
            <p class="mt-4 rounded-lg border border-amber-200/80 bg-amber-50/80 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-100">
                {{ module_label($module, 'settings_admin_only', 'module_settings_admin_only') }}
            </p>
        @endif

        @if ($manifestKeys === [])
            <p class="mt-4 text-sm text-slate-500">{{ module_label($module, 'settings_no_integrations', 'module_settings_no_integrations') }}</p>
        @else
            <form method="post" action="{{ route('modules.actions', $module->slug) }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="module_action" value="save_integrations">
                <input type="hidden" name="return_page" value="settings">

                <fieldset @disabled(! $isAdmin) class="space-y-3">
                    <legend class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ module_label($module, 'settings_integrations_heading', 'module_settings_integrations_heading') }}</legend>

                    @foreach ($manifestKeys as $key)
                        @php
                            $planOk = $company ? $planLimits->isFeatureEnabled($company, $key) : false;
                            $checked = $planOk && ($enabled[$key] ?? false);
                        @endphp
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-white p-4 dark:border-slate-700/80 dark:bg-slate-900/40 @if(! $planOk) opacity-60 @endif">
                            <input
                                type="checkbox"
                                name="integrations[{{ $key }}]"
                                value="1"
                                @checked($checked)
                                @disabled(! $planOk || ! $isAdmin)
                                class="mt-0.5 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800"
                            />
                            <span>
                                <span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                @if (! $planOk)
                                    <span class="mt-1 block text-xs text-slate-500">{{ module_label($module, 'settings_plan_required', 'module_settings_plan_required', ['feature' => ucfirst($key)]) }}</span>
                                @elseif ($key === 'calendar')
                                    <span class="mt-1 block text-xs text-slate-500">{{ module_label($module, 'settings_calendar_help', 'module_settings_calendar_help') }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                @if ($isAdmin)
                    <x-primary-button type="submit" class="!normal-case">{{ __('Save') }}</x-primary-button>
                @endif
            </form>
        @endif
    </div>
</div>
