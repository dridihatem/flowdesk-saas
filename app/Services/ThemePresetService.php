<?php

namespace App\Services;

use App\Models\PlatformSetting;

class ThemePresetService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $base = config('flowdesk.theme_presets', []);
        $base = is_array($base) ? $base : [];

        $row = PlatformSetting::query()->first();
        $library = $row?->theme_library;
        $library = is_array($library) ? $library : [];

        // Expect library to be keyed by preset key.
        foreach ($library as $key => $preset) {
            if (! is_string($key) || $key === '' || ! is_array($preset)) {
                continue;
            }
            $base[$key] = $preset;
        }

        ksort($base);

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $key): array
    {
        $all = $this->all();

        $preset = $all[$key] ?? [];

        return is_array($preset) ? $preset : [];
    }

    /**
     * Only palette fields from a preset (config or theme library).
     * Library presets may include font_family / dark_mode for previews — those must not overwrite the appearance form.
     *
     * @return array{primary_color?: string, secondary_color?: string, background_color?: string}
     */
    public function colorOverridesOnly(string $key): array
    {
        $preset = $this->get($key);
        if ($preset === []) {
            return [];
        }

        $out = [];
        foreach (['primary_color', 'secondary_color', 'background_color'] as $field) {
            if (! empty($preset[$field]) && is_string($preset[$field])) {
                $out[$field] = $preset[$field];
            }
        }

        return $out;
    }
}
