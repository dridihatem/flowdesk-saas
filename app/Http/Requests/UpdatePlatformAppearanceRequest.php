<?php

namespace App\Http\Requests;

use App\Services\ThemePresetService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole('platform_admin');
    }

    public function rules(): array
    {
        $presets = array_keys(app(ThemePresetService::class)->all());
        $fonts = array_keys(config('flowdesk.font_urls', []));

        return [
            'theme_name' => ['required', 'string', Rule::in($presets)],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => ['required', 'string', Rule::in($fonts)],
            'dark_mode' => ['required', 'string', Rule::in(['light', 'dark', 'system'])],
            'custom_css' => ['nullable', 'string', 'max:50000'],
            'apply_preset_colors' => ['sometimes', 'boolean'],
        ];
    }
}
