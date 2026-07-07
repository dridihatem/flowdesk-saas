<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InstalledModule;

class ModuleSettingsService
{
    public function __construct(
        private PlanLimitService $planLimits,
    ) {}

    /**
     * Integration keys declared in the module manifest.
     *
     * @return list<string>
     */
    public function manifestIntegrations(InstalledModule $module): array
    {
        $integrations = $module->manifest['integrations'] ?? [];
        if (! is_array($integrations)) {
            return [];
        }

        return array_values(array_filter(
            array_keys($integrations),
            fn (string $key) => (bool) ($integrations[$key] ?? false),
        ));
    }

    /**
     * User-enabled integrations stored on the installed module record.
     *
     * @return array<string, bool>
     */
    public function enabledIntegrations(InstalledModule $module): array
    {
        $settings = $module->settings ?? [];
        $stored = $settings['integrations'] ?? [];
        if (! is_array($stored)) {
            return [];
        }

        $out = [];
        foreach ($this->manifestIntegrations($module) as $key) {
            $out[$key] = (bool) ($stored[$key] ?? false);
        }

        return $out;
    }

    public function isIntegrationEnabled(InstalledModule $module, Company $company, string $key): bool
    {
        if (! in_array($key, $this->manifestIntegrations($module), true)) {
            return false;
        }

        if (! $this->planLimits->isFeatureEnabled($company, $key)) {
            return false;
        }

        return (bool) ($this->enabledIntegrations($module)[$key] ?? false);
    }

    /**
     * @param  array<string, mixed>  $toggles
     */
    public function saveIntegrations(InstalledModule $module, array $toggles): void
    {
        $allowed = $this->manifestIntegrations($module);
        $integrations = [];

        foreach ($allowed as $key) {
            $integrations[$key] = (bool) ($toggles[$key] ?? false);
        }

        $settings = $module->settings ?? [];
        $settings['integrations'] = $integrations;

        $module->update(['settings' => $settings]);
    }
}
