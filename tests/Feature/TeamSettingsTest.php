<?php

use App\Models\User;

test('company admin can view team settings', function () {
    $user = User::factory()->create();
    $user->assignRole('company_admin');

    $response = $this->actingAs($user)->get(route('settings.team'));

    $response->assertOk();
});

test('team member cannot access team settings', function () {
    $user = User::factory()->create();
    $user->syncRoles(['team_member']);

    $response = $this->actingAs($user)->get(route('settings.team'));

    $response->assertForbidden();
});

test('team settings list excludes client portal users', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(['company_admin']);
    $clientUser = User::factory()->create(['company_id' => $admin->company_id]);
    $clientUser->syncRoles(['client']);

    $response = $this->actingAs($admin)->get(route('settings.team'));

    $response->assertOk();
    $response->assertDontSee($clientUser->email);
});
