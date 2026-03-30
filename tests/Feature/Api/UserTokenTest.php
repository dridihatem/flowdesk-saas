<?php

use App\Models\User;

test('sanctum token can access api user endpoint', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->getJson('/api/user', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertOk()->assertJsonFragment(['email' => $user->email]);
});
