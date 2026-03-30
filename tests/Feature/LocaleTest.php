<?php

use App\Models\User;

test('guest can change session locale', function () {
    $response = $this->from('/login')->post('/locale', ['locale' => 'fr']);

    $response->assertRedirect('/login');
    expect(session('locale'))->toBe('fr');
});

test('authenticated user can persist locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/dashboard')->post('/locale', ['locale' => 'es']);

    expect($user->fresh()->locale)->toBe('es');
});
