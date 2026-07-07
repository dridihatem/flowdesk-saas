<?php

use App\Models\User;
use App\Services\NovaIdentityService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova identity service detects who are you questions', function () {
    $service = app(NovaIdentityService::class);

    expect($service->isIdentityQuestion('Who are you?'))->toBeTrue();
    expect($service->isIdentityQuestion('Nova, what can you do?'))->toBeTrue();
    expect($service->isIdentityQuestion('How much revenue this month?'))->toBeFalse();
});

test('assistant chat returns identity reply without charging credits', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.chat'), [
            'message' => 'Who are you?',
        ])
        ->assertOk()
        ->assertJsonPath('identity', true)
        ->assertJsonPath('ai_credits_charged', 0)
        ->assertJsonFragment([
            'reply' => app(NovaIdentityService::class)->reply($user->company, $user),
        ]);
});
