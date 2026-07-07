<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('platform admin can view admin dashboard', function () {
    $user = User::factory()->platformAdmin()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});

test('platform admin can view platform reports', function () {
    $user = User::factory()->platformAdmin()->create();

    $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
});

test('platform admin can view workspace theme defaults', function () {
    $user = User::factory()->platformAdmin()->create();

    $this->actingAs($user)->get(route('admin.platform-appearance.edit'))->assertOk();
});

test('platform admin can view payment gateway settings', function () {
    $user = User::factory()->platformAdmin()->create();

    $this->actingAs($user)->get(route('admin.payment-gateways.edit'))->assertOk();
});

test('platform admin can view and update profile in admin layout', function () {
    $user = User::factory()->platformAdmin()->create([
        'name' => 'Admin User',
        'email' => 'admin-profile@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee('Admin User')
        ->assertSee('admin-profile@example.com');

    $this->actingAs($user)
        ->patch(route('admin.profile.update'), [
            'name' => 'Updated Admin',
            'email' => 'updated-admin@example.com',
            'locale' => 'en',
        ])
        ->assertRedirect(route('admin.profile.edit'));

    expect($user->fresh()->name)->toBe('Updated Admin');
    expect($user->fresh()->email)->toBe('updated-admin@example.com');
});

test('company admin cannot view admin profile page', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)->get(route('admin.profile.edit'))->assertForbidden();
});

test('company admin cannot view admin dashboard', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});
