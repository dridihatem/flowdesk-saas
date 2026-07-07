<?php

use App\Models\Company;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanLimitService;

test('plan limit value zero disables feature and blocks route', function () {
    $plan = Plan::factory()->create();
    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'email_marketing',
        'limit_value' => 0,
    ]);
    $company = Company::factory()->create();
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->syncRoles(['team_member']);

    $limits = app(PlanLimitService::class);
    expect($limits->isFeatureEnabled($company, 'email_marketing'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('email-marketing.index'))
        ->assertForbidden();
});

test('plan without limit row keeps feature enabled', function () {
    $plan = Plan::factory()->create();
    $company = Company::factory()->create();
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    expect(app(PlanLimitService::class)->isFeatureEnabled($company, 'email_marketing'))->toBeTrue();
});
