<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\ThemePresetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ThemeLibraryController extends Controller
{
    public function index(ThemePresetService $presets): View
    {
        $row = PlatformSetting::query()->first();
        $library = $row?->theme_library;
        $library = is_array($library) ? $library : [];

        return view('admin.themes.index', [
            'library' => $library,
            'allPresets' => $presets->all(),
            'fonts' => array_keys(config('flowdesk.font_urls', [])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'label' => ['required', 'string', 'max:64'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => ['required', 'string', Rule::in(array_keys(config('flowdesk.font_urls', [])))],
            'dark_mode' => ['required', 'string', Rule::in(['light', 'dark'])],
        ]);

        $row = PlatformSetting::query()->first() ?? new PlatformSetting;
        $library = is_array($row->theme_library) ? $row->theme_library : [];

        $library[$data['key']] = [
            'label' => $data['label'],
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'background_color' => $data['background_color'] ?? null,
            'font_family' => $data['font_family'],
            'dark_mode' => $data['dark_mode'],
        ];

        $row->theme_library = $library;
        $row->save();

        return redirect()->route('admin.themes.index')->with('status', __('Theme saved.'));
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        $row = PlatformSetting::query()->first();
        abort_if(! $row, 404);

        $library = is_array($row->theme_library) ? $row->theme_library : [];
        unset($library[$key]);
        $row->theme_library = $library;
        $row->save();

        return redirect()->route('admin.themes.index')->with('status', __('Theme removed.'));
    }
}
