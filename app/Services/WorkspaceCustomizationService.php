<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Support\Str;

class WorkspaceCustomizationService
{
    public function __construct(
        private CompanyThemeService $themes,
    ) {}

    public function ensureSettings(Company $company): CompanySetting
    {
        return $this->themes->ensureSettings($company);
    }

    /**
     * Resolved widget rows: key, label, enabled, order.
     *
     * @return list<array{key: string, label: string, enabled: bool, order: int}>
     */
    public function resolvedWidgets(Company $company): array
    {
        $definitions = config('flowdesk.dashboard_widgets', []);
        $settings = $this->ensureSettings($company);
        $stored = is_array($settings->dashboard) ? $settings->dashboard : [];
        $rows = is_array($stored['widgets'] ?? null) ? $stored['widgets'] : [];

        $byKey = [];
        foreach ($rows as $row) {
            if (! empty($row['key'])) {
                $byKey[$row['key']] = $row;
            }
        }

        $ordered = [];
        $fallbackOrder = 0;
        foreach (array_keys($definitions) as $key) {
            $row = $byKey[$key] ?? [];
            $ordered[] = [
                'key' => $key,
                'label' => $definitions[$key]['label'] ?? $key,
                'enabled' => (bool) ($row['enabled'] ?? true),
                'order' => isset($row['order']) ? (int) $row['order'] : $fallbackOrder,
            ];
            $fallbackOrder++;
        }

        usort($ordered, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $ordered;
    }

    /**
     * @param  list<array{key: string, enabled?: bool, order?: int}>  $widgets
     */
    public function saveDashboardLayout(Company $company, array $widgets): void
    {
        $settings = $this->ensureSettings($company);
        $dashboard = is_array($settings->dashboard) ? $settings->dashboard : [];
        $dashboard['widgets'] = array_values($widgets);
        $settings->dashboard = $dashboard;
        $settings->save();
    }

    /**
     * @return list<array{id: string, name: string, created_at: string|null}>
     */
    public function listPresets(Company $company): array
    {
        $settings = $this->ensureSettings($company);
        $presets = is_array($settings->ui_presets) ? $settings->ui_presets : [];

        return array_map(fn ($p) => [
            'id' => (string) ($p['id'] ?? ''),
            'name' => (string) ($p['name'] ?? ''),
            'created_at' => isset($p['created_at']) ? (string) $p['created_at'] : null,
        ], $presets);
    }

    /**
     * Snapshot current theme + dashboard into a named preset.
     */
    public function savePreset(Company $company, string $name): string
    {
        $settings = $this->ensureSettings($company);
        $presets = is_array($settings->ui_presets) ? $settings->ui_presets : [];

        $id = (string) Str::ulid();
        $presets[] = [
            'id' => $id,
            'name' => $name,
            'theme' => is_array($settings->theme) ? $settings->theme : [],
            'dashboard' => is_array($settings->dashboard) ? $settings->dashboard : [],
            'created_at' => now()->toIso8601String(),
        ];

        $settings->ui_presets = array_values($presets);
        $settings->save();

        return $id;
    }

    public function activatePreset(Company $company, string $presetId): void
    {
        $settings = $this->ensureSettings($company);
        $presets = is_array($settings->ui_presets) ? $settings->ui_presets : [];
        $found = null;
        foreach ($presets as $p) {
            if (($p['id'] ?? '') === $presetId) {
                $found = $p;
                break;
            }
        }
        abort_if($found === null, 404);

        $theme = is_array($found['theme'] ?? null) ? $found['theme'] : [];
        $dashboard = is_array($found['dashboard'] ?? null) ? $found['dashboard'] : [];

        $settings->theme = array_merge(is_array($settings->theme) ? $settings->theme : [], $theme);
        $settings->dashboard = $dashboard;
        $settings->save();
    }

    public function deletePreset(Company $company, string $presetId): void
    {
        $settings = $this->ensureSettings($company);
        $presets = is_array($settings->ui_presets) ? $settings->ui_presets : [];
        $settings->ui_presets = array_values(array_filter(
            $presets,
            fn ($p) => ($p['id'] ?? '') !== $presetId,
        ));
        $settings->save();
    }
}
