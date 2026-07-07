<?php

use App\Models\Plan;
use App\Services\PlanLimitService;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    $this->seed(PlanSeeder::class);
});

function planFeatureEnabled(string $slug, string $key): bool
{
    $plan = Plan::query()->where('slug', $slug)->with('limits')->firstOrFail();
    $rows = app(PlanLimitService::class)->summarizePlanFeatures($plan);

    $row = collect($rows)->firstWhere('key', $key);

    return $row !== null && $row['enabled'] === true;
}

function planFeatureStatus(string $slug, string $key): string
{
    $plan = Plan::query()->where('slug', $slug)->with('limits')->firstOrFail();
    $rows = app(PlanLimitService::class)->summarizePlanFeatures($plan);

    $row = collect($rows)->firstWhere('key', $key);

    return $row['status'] ?? '';
}

test('starter plan seeds all catalog features with expected active and disabled flags', function () {
    $plan = Plan::query()->where('slug', 'starter')->with('limits')->firstOrFail();

    expect($plan->limits)->toHaveCount(count(PlanLimitService::FEATURE_KEYS));

    $enabled = [
        'projects', 'users', 'forms', 'submissions', 'widgets', 'ai_credits',
    ];
    $disabled = [
        'analytics', 'marketing_hub', 'email_marketing', 'reports',
        'providers', 'calendar', 'modules', 'premium_tts', 'workspace_ai_agent',
    ];

    foreach ($enabled as $key) {
        expect(planFeatureEnabled('starter', $key))->toBeTrue("starter: {$key} should be enabled");
    }
    foreach ($disabled as $key) {
        expect(planFeatureEnabled('starter', $key))->toBeFalse("starter: {$key} should be disabled");
        expect(planFeatureStatus('starter', $key))->toBe(__('Not included'));
    }
});

test('pro plan enables growth and workspace features with quotas', function () {
    $enabled = PlanLimitService::FEATURE_KEYS;
    foreach ($enabled as $key) {
        expect(planFeatureEnabled('pro', $key))->toBeTrue("pro: {$key} should be enabled");
    }

    expect(planFeatureStatus('pro', 'projects'))->toContain('100');
    expect(planFeatureStatus('pro', 'providers'))->toContain('25');
});

test('enterprise plan enables all features with unlimited quotas where applicable', function () {
    foreach (PlanLimitService::FEATURE_KEYS as $key) {
        expect(planFeatureEnabled('enterprise', $key))->toBeTrue("enterprise: {$key} should be enabled");
    }

    expect(planFeatureStatus('enterprise', 'projects'))->toBe(__('Unlimited'));
    expect(planFeatureStatus('enterprise', 'analytics'))->toBe(__('Included'));
});

test('each seeded plan defines every catalog feature explicitly', function () {
    $catalogKeys = app(PlanLimitService::class)->featureCatalogKeys();

    foreach (['starter', 'pro', 'enterprise'] as $slug) {
        $plan = Plan::query()->where('slug', $slug)->with('limits')->firstOrFail();
        expect($plan->limits->pluck('feature_key')->sort()->values()->all())
            ->toEqual(collect($catalogKeys)->sort()->values()->all());
    }
});
