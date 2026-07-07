<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePlatformAppearanceRequest;
use App\Models\PlatformSetting;
use App\Services\CompanyThemeService;
use App\Services\ThemePresetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAppearanceController extends Controller
{
    public function edit(Request $request, CompanyThemeService $themes, ThemePresetService $presets): View
    {
        $base = $themes->baseThemeDefaults();
        $resolved = $themes->themeFor(null, $request->user());

        return view('admin.platform-appearance', [
            'theme' => $resolved,
            'rawTheme' => $base,
            'presets' => $presets->all(),
            'fonts' => array_keys(config('flowdesk.font_urls', [])),
        ]);
    }

    public function update(UpdatePlatformAppearanceRequest $request, CompanyThemeService $themes, ThemePresetService $presets): RedirectResponse
    {
        $validated = $request->validated();

        $theme = [
            'theme_name' => $validated['theme_name'],
            'layout_type' => 'sidebar',
            'font_family' => $validated['font_family'],
            'dark_mode' => $validated['dark_mode'],
            'custom_css' => $validated['custom_css'] ?? null,
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'],
        ];

        if ($request->boolean('apply_preset_colors')) {
            $colorPatch = $presets->colorOverridesOnly($validated['theme_name']);
            if ($colorPatch !== []) {
                $theme = array_merge($theme, $colorPatch);
            }
        }

        $row = PlatformSetting::query()->first() ?? new PlatformSetting;
        $existing = is_array($row->theme_defaults) ? $row->theme_defaults : [];
        $row->theme_defaults = array_merge($existing, $theme);
        $row->save();

        return redirect()->route('admin.platform-appearance.edit')->with('status', __('Default workspace appearance saved. Companies inherit these defaults until they customize appearance in their own settings.'));
    }
}
