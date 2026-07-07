<?php

use App\Models\Company;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Subscription;
use App\Models\UsageTracking;
use App\Services\PlanLimitService;

test('ai credit plan limit uses current month usage only', function () {
    $plan = Plan::factory()->create();
    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'ai_credits',
        'limit_value' => 2,
    ]);
    $company = Company::factory()->create();
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $limits = app(PlanLimitService::class);

    UsageTracking::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'metric' => 'ai_credits',
        'value' => 50,
        'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
        'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
    ]);

    expect($limits->allows($company, 'ai_credits'))->toBeTrue();

    UsageTracking::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'metric' => 'ai_credits',
        'value' => 1,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
    ]);
    expect($limits->allows($company, 'ai_credits'))->toBeTrue();

    UsageTracking::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'metric' => 'ai_credits',
        'value' => 1,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
    ]);
    expect($limits->allows($company, 'ai_credits'))->toBeFalse();
    expect($limits->allows($company, 'ai_credits', 100))->toBeFalse();
});

test('ai credit plan allows task when enough remaining credits', function () {
    $plan = Plan::factory()->create();
    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'ai_credits',
        'limit_value' => 500,
    ]);
    $company = Company::factory()->create();
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    UsageTracking::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'metric' => 'ai_credits',
        'value' => 150,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $limits = app(PlanLimitService::class);

    expect($limits->allows($company, 'ai_credits', 100))->toBeTrue();
    expect($limits->allows($company, 'ai_credits', 250))->toBeTrue();
    expect($limits->allows($company, 'ai_credits', 351))->toBeFalse();
});
