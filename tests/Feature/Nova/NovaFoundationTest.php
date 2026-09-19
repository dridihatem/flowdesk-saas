<?php

use App\AI\Nova\Assignment\AssignmentEngine;
use App\AI\Nova\Memory\ContextBuilder;
use App\AI\Nova\Project\ProjectPlanner;
use App\AI\Nova\Support\ActionRisk;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\NovaSkillSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(NovaSkillSeeder::class);
});

test('action risk marks delete and send as high', function () {
    expect(ActionRisk::forTool('projects.delete'))->toBe(ActionRisk::HIGH)
        ->and(ActionRisk::forTool('invoices.send'))->toBe(ActionRisk::HIGH)
        ->and(ActionRisk::forTool('clients.search'))->toBe(ActionRisk::LOW)
        ->and(ActionRisk::requiresConfirmation(ActionRisk::HIGH))->toBeTrue();
});

test('project planner returns preview schema without writing db', function () {
    $user = User::factory()->create();
    $context = app(ContextBuilder::class)->build($user, $user->company);
    $plan = app(ProjectPlanner::class)->fromRequirements([
        'title' => 'E-commerce Platform',
        'summary' => 'Laravel Stripe checkout',
        'deliverables' => ['Discovery', 'Stripe Integration', 'Launch'],
    ], $context);

    expect($plan)->toHaveKeys(['project', 'phases', 'tasks', 'dependencies', 'preview', 'requires_confirmation'])
        ->and($plan['preview']['task_count'])->toBe(3)
        ->and($plan['requires_confirmation'])->toBeTrue()
        ->and($plan['required_skills'])->toContain('Laravel');
});

test('assignment engine scores candidates deterministically', function () {
    $user = User::factory()->create();
    $context = app(ContextBuilder::class)->build($user, $user->company);
    $result = app(AssignmentEngine::class)->recommend($context, null, ['Laravel', 'PHP']);

    expect($result)->toHaveKeys(['weights', 'candidates', 'explanation', 'mode'])
        ->and($result['weights']['skill'])->toBe(0.4)
        ->and($result['auto_assign_allowed'])->toBeFalse();
});

test('skills catalog is seedable', function () {
    expect(Skill::query()->whereNull('company_id')->count())->toBeGreaterThanOrEqual(10);
});
