<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\PublicDiskUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class CompanyThemeService
{
    public function ensureSettings(Company $company): CompanySetting
    {
        return CompanySetting::query()->firstOrCreate(
            ['company_id' => $company->id],
            ['theme' => []],
        );
    }

    /**
     * Defaults from config plus platform-admin overrides (applies to all new and existing companies until they save their own theme).
     *
     * @return array<string, mixed>
     */
    public function baseThemeDefaults(): array
    {
        $platform = PlatformSetting::query()->first()?->theme_defaults;
        $platform = is_array($platform) ? $platform : [];

        return array_merge(config('flowdesk.theme_defaults', []), $platform);
    }

    /**
     * Whitelist for optional per-user JSON overrides (users.appearance_json).
     *
     * @var list<string>
     */
    private const USER_APPEARANCE_KEYS = [
        'font_family',
        'dark_mode',
        'primary_color',
        'secondary_color',
        'custom_css',
        'theme_name',
    ];

    /**
     * @return array<string, mixed>
     */
    public function themeFor(?Company $company, ?Authenticatable $user = null): array
    {
        $defaults = $this->baseThemeDefaults();

        if (! $company) {
            $merged = $defaults;
        } else {
            $settings = $this->ensureSettings($company);
            $stored = is_array($settings->theme) ? $settings->theme : [];
            $merged = array_merge($defaults, $stored);
        }

        if ($user instanceof User) {
            $merged = $this->mergeUserAppearanceOverrides($merged, $user);
        }

        return $this->normalizeTheme($merged);
    }

    /**
     * @param  array<string, mixed>  $merged
     * @return array<string, mixed>
     */
    private function mergeUserAppearanceOverrides(array $merged, User $user): array
    {
        $json = $user->appearance_json;
        if (! is_array($json) || $json === []) {
            return $merged;
        }

        $patch = array_intersect_key($json, array_flip(self::USER_APPEARANCE_KEYS));

        return array_merge($merged, $patch);
    }

    public function layoutView(?Company $company, ?Authenticatable $user = null): string
    {
        $theme = $this->themeFor($company, $user);
        $themeName = preg_replace('/[^a-z0-9_]/', '', (string) ($theme['theme_name'] ?? 'default')) ?: 'default';
        $layout = 'sidebar';

        $candidate = "themes.{$themeName}.layouts.{$layout}";
        if (View::exists($candidate)) {
            return $candidate;
        }

        return 'themes.default.layouts.sidebar';
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public function saveTheme(Company $company, array $theme): void
    {
        $settings = $this->ensureSettings($company);
        $existing = is_array($settings->theme) ? $settings->theme : [];
        $settings->theme = array_merge($existing, $theme);
        $settings->save();
    }

    public function storeLogo(Company $company, UploadedFile $file): void
    {
        $settings = $this->ensureSettings($company);
        $theme = is_array($settings->theme) ? $settings->theme : [];
        if (! empty($theme['logo_path'])) {
            Storage::disk('public')->delete($theme['logo_path']);
        }

        $dir = 'tenants/'.$company->id.'/branding';
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true) ? $ext : 'png';
        $path = $file->storeAs($dir, 'logo.'.$ext, 'public');

        $theme['logo_path'] = $path;
        $settings->theme = $theme;
        $settings->save();
    }

    public function deleteLogo(Company $company): void
    {
        $settings = $this->ensureSettings($company);
        $theme = is_array($settings->theme) ? $settings->theme : [];
        if (! empty($theme['logo_path'])) {
            Storage::disk('public')->delete($theme['logo_path']);
        }
        $theme['logo_path'] = null;
        $settings->theme = $theme;
        $settings->save();
    }

    public function storeSignature(Company $company, UploadedFile $file): void
    {
        $settings = $this->ensureSettings($company);
        $theme = is_array($settings->theme) ? $settings->theme : [];
        if (! empty($theme['signature_path'])) {
            Storage::disk('public')->delete($theme['signature_path']);
        }

        $dir = 'tenants/'.$company->id.'/branding';
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true) ? $ext : 'png';
        $path = $file->storeAs($dir, 'signature.'.$ext, 'public');

        $theme['signature_path'] = $path;
        $settings->theme = $theme;
        $settings->save();
    }

    public function deleteSignature(Company $company): void
    {
        $settings = $this->ensureSettings($company);
        $theme = is_array($settings->theme) ? $settings->theme : [];
        if (! empty($theme['signature_path'])) {
            Storage::disk('public')->delete($theme['signature_path']);
        }
        $theme['signature_path'] = null;
        $settings->theme = $theme;
        $settings->save();
    }

    /**
     * Inline image for DomPDF (avoids remote URL fetch).
     */
    public function logoDataUriForPdf(?Company $company): ?string
    {
        if (! $company) {
            return null;
        }

        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $theme = is_array($settings?->theme) ? $settings->theme : [];
        $path = $theme['logo_path'] ?? null;
        if ($path === null || $path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            return null;
        }

        $mime = @mime_content_type($fullPath) ?: 'image/png';
        $binary = @file_get_contents($fullPath);
        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    public function signatureDataUriForPdf(?Company $company): ?string
    {
        if (! $company) {
            return null;
        }

        $settings = CompanySetting::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $theme = is_array($settings?->theme) ? $settings->theme : [];
        $path = $theme['signature_path'] ?? null;
        if ($path === null || $path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            return null;
        }

        $mime = @mime_content_type($fullPath) ?: 'image/png';
        $binary = @file_get_contents($fullPath);
        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * @param  array<string, mixed>  $theme
     * @return array<string, mixed>
     */
    private function normalizeTheme(array $theme): array
    {
        $primary = (string) ($theme['primary_color'] ?? '#2563eb');
        if (! Str::startsWith($primary, '#')) {
            $primary = '#'.$primary;
        }
        $secondary = (string) ($theme['secondary_color'] ?? '#64748b');
        if (! Str::startsWith($secondary, '#')) {
            $secondary = '#'.$secondary;
        }

        $font = $this->canonicalFontFamily((string) ($theme['font_family'] ?? 'Figtree'));
        $darkMode = $theme['dark_mode'] ?? 'system';
        if ($darkMode === true) {
            $darkMode = 'dark';
        }
        if ($darkMode === false) {
            $darkMode = 'light';
        }

        $logoUrl = null;
        if (! empty($theme['logo_path'])) {
            $logoUrl = PublicDiskUrl::forPath((string) $theme['logo_path']);
        }

        $signatureUrl = null;
        if (! empty($theme['signature_path'])) {
            $signatureUrl = PublicDiskUrl::forPath((string) $theme['signature_path']);
        }

        $fontStack = $this->fontStack($font);

        $fontUrls = config('flowdesk.font_urls', []);

        return [
            'theme_name' => (string) ($theme['theme_name'] ?? 'default'),
            'layout_type' => 'sidebar',
            'primary_color' => $primary,
            'secondary_color' => $secondary,
            'font_family' => $font,
            'font_stack' => $fontStack,
            'font_url' => $fontUrls[$font] ?? reset($fontUrls) ?: null,
            'dark_mode' => $darkMode,
            'logo_path' => $theme['logo_path'] ?? null,
            'logo_url' => $logoUrl,
            'signature_path' => $theme['signature_path'] ?? null,
            'signature_url' => $signatureUrl,
            'custom_css' => isset($theme['custom_css']) ? (string) $theme['custom_css'] : null,
            'primary_hover' => $this->darkenHex($primary, 12),
            'html_class' => $darkMode === 'dark' ? 'dark' : '',
            'use_system_dark' => $darkMode === 'system',
        ];
    }

    /**
     * Match stored font name to config key (case/spacing) so Bunny CSS and font-family agree.
     */
    public function canonicalFontFamily(string $input): string
    {
        $fonts = array_keys(config('flowdesk.font_urls', []));
        foreach ($fonts as $name) {
            if (strcasecmp((string) $name, $input) === 0) {
                return (string) $name;
            }
        }

        return $fonts[0] ?? 'Figtree';
    }

    private function fontStack(string $font): string
    {
        $safe = str_replace(["'", '"'], '', $font);

        return "'{$safe}', ui-sans-serif, system-ui, sans-serif";
    }

    private function darkenHex(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#1d4ed8';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $factor = max(0.0, min(1.0, 1 - ($percent / 100)));
        $r = (int) round($r * $factor);
        $g = (int) round($g * $factor);
        $b = (int) round($b * $factor);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
