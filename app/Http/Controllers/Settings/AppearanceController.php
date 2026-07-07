<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompanyAppearanceRequest;
use App\Services\CompanyThemeService;
use App\Services\ThemePresetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function edit(Request $request, CompanyThemeService $themes, ThemePresetService $presets): View
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

        $resolved = $themes->themeFor($company, $request->user());
        $raw = $themes->ensureSettings($company)->theme;
        $raw = is_array($raw) ? $raw : [];

        return view('settings.appearance', [
            'theme' => $resolved,
            'rawTheme' => array_merge($themes->baseThemeDefaults(), $raw),
            'presets' => $presets->all(),
            'fonts' => array_keys(config('flowdesk.font_urls', [])),
        ]);
    }

    public function update(UpdateCompanyAppearanceRequest $request, CompanyThemeService $themes, ThemePresetService $presets): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 403);

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

        $themes->saveTheme($company, $theme);

        if ($request->boolean('remove_logo')) {
            $themes->deleteLogo($company);
        }

        if ($request->hasFile('logo')) {
            $themes->storeLogo($company, $request->file('logo'));
        }

        if ($request->boolean('remove_signature')) {
            $themes->deleteSignature($company);
        }

        if ($request->hasFile('signature')) {
            $themes->storeSignature($company, $request->file('signature'));
        }

        return redirect()->route('settings.appearance')->with('status', __('Appearance saved.'));
    }
}
