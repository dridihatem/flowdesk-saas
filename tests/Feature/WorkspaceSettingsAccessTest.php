<?php

use App\Models\User;

test('team member can view workspace smtp settings', function () {
    $user = User::factory()->create();
    $user->syncRoles(['team_member']);

    $this->actingAs($user)->get(route('settings.smtp'))->assertOk();
});
