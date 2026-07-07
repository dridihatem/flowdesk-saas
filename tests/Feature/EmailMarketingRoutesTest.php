<?php

use App\Models\Company;
use App\Models\User;

test('workspace staff can open email marketing hub', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['team_member']);

    $this->actingAs($user)
        ->get(route('email-marketing.index'))
        ->assertOk();
});

test('email marketing subpages respond for workspace staff', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['team_member']);

    $this->actingAs($user)
        ->get(route('email-marketing.campaigns.index'))
        ->assertOk();
    $this->actingAs($user)
        ->get(route('email-marketing.templates.index'))
        ->assertOk();
    $this->actingAs($user)
        ->get(route('email-marketing.audiences.index'))
        ->assertOk();
    $this->actingAs($user)
        ->get(route('email-marketing.sequences.index'))
        ->assertOk();
});
