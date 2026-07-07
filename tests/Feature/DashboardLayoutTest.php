<?php

use App\Models\Company;
use App\Models\User;

test('company admin can save dashboard layout as json', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['company_admin']);

    $widgets = collect(array_keys(config('flowdesk.dashboard_widgets', [])))
        ->values()
        ->map(fn (string $key, int $i) => [
            'key' => $key,
            'enabled' => true,
            'order' => $i,
        ])
        ->all();

    $response = $this->actingAs($user)
        ->putJson(route('settings.dashboard.update'), ['widgets' => $widgets]);

    $response->assertOk()->assertJson(['message' => 'ok']);
});

test('team member can save dashboard layout as json', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['team_member']);

    $widgets = collect(array_keys(config('flowdesk.dashboard_widgets', [])))
        ->values()
        ->map(fn (string $key, int $i) => [
            'key' => $key,
            'enabled' => true,
            'order' => $i,
        ])
        ->all();

    $response = $this->actingAs($user)
        ->putJson(route('settings.dashboard.update'), ['widgets' => $widgets]);

    $response->assertOk()->assertJson(['message' => 'ok']);
});
