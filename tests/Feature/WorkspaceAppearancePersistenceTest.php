<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\CompanyThemeService;

test('apply preset colors only merges palette from library preset not font or dark mode', function () {
    $row = PlatformSetting::query()->first() ?? new PlatformSetting;
    $row->theme_defaults = is_array($row->theme_defaults) ? $row->theme_defaults : [];
    $library = is_array($row->theme_library) ? $row->theme_library : [];
    $library['lib_preset'] = [
        'label' => 'Lib',
        'primary_color' => '#111111',
        'secondary_color' => '#222222',
        'background_color' => '#ffffff',
        'font_family' => 'Inter',
        'dark_mode' => 'dark',
    ];
    $row->theme_library = $library;
    $row->save();

    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    $company = $user->company;
    expect($company)->not->toBeNull();

    $this->actingAs($user)
        ->put(route('settings.appearance.update'), [
            'theme_name' => 'lib_preset',
            'primary_color' => '#333333',
            'secondary_color' => '#444444',
            'font_family' => 'Nunito',
            'dark_mode' => 'light',
            'apply_preset_colors' => '1',
            'custom_css' => null,
        ])
        ->assertRedirect(route('settings.appearance'));

    $stored = app(CompanyThemeService::class)->ensureSettings($company)->fresh()->theme;
    expect($stored['font_family'] ?? null)->toBe('Nunito')
        ->and($stored['dark_mode'] ?? null)->toBe('light')
        ->and($stored['primary_color'] ?? null)->toBe('#111111')
        ->and($stored['secondary_color'] ?? null)->toBe('#222222')
        ->and($stored['background_color'] ?? null)->toBe('#ffffff');
});

test('user appearance_json overrides company theme for resolved theme', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    $company = $user->company;
    expect($company)->not->toBeNull();

    app(CompanyThemeService::class)->saveTheme($company, [
        'font_family' => 'Figtree',
        'primary_color' => '#2563eb',
        'secondary_color' => '#64748b',
    ]);

    $user->appearance_json = ['font_family' => 'Inter'];
    $user->save();

    $resolved = app(CompanyThemeService::class)->themeFor($company, $user);
    expect($resolved['font_family'])->toBe('Inter')
        ->and($resolved['primary_color'])->toBe('#2563eb');
});
