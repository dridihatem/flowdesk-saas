<?php

use App\Models\Company;
use App\Models\User;

test('workspace manager can regenerate widget api token', function () {
    $company = Company::factory()->create(['api_token_hash' => null, 'api_token_hint' => null]);
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['team_member']);

    $response = $this->actingAs($user)->post(route('settings.widget-embed.regenerate-token'));

    $response->assertRedirect(route('settings.widget-embed'));
    $company->refresh();
    expect($company->api_token_hash)->not->toBeNull();
    expect($company->api_token_hint)->not->toBeNull();
});
