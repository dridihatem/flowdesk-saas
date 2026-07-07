<?php

use App\Services\PlanLimitService;

test('plan feature catalog keys match FEATURE_KEYS constant', function () {
    $catalogKeys = app(PlanLimitService::class)->featureCatalogKeys();

    expect($catalogKeys)->toEqual(PlanLimitService::FEATURE_KEYS);
});

test('admin plan edit uses the same feature catalog as billing subscription', function () {
    $catalog = app(PlanLimitService::class)->featureCatalog();

    expect($catalog)->not->toBeEmpty();
    expect(collect($catalog)->pluck('key')->all())->toContain('calendar', 'modules', 'workspace_ai_agent');
});
