<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('settings hub shows search and setting cards', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->get(route('settings.workspace'))
        ->assertOk()
        ->assertSee('id="settings-hub-search"', false)
        ->assertSee('data-settings-search-row', false)
        ->assertSee(__('settings_modules_title'), false);
});

test('settings hub group sub navigation renders in arabic locale', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->withSession(['locale' => 'ar'])
        ->get(route('settings.workspace'))
        ->assertOk()
        ->assertSee('settings-group-0', false)
        ->assertSee(__('Settings group appearance'), false);
});

test('sidebar flyout teleports submenu panel for rtl-safe positioning', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)
        ->withSession(['locale' => 'ar'])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('x-teleport="body"', false)
        ->assertSee('flow-nav-flyout-panel', false);
});
