<?php

use App\Models\Company;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function seedPaidPremiumTtsForCompany(Company $company, int $aiCredits = 10000): Plan
{
    $plan = Plan::factory()->create(['price_monthly' => 79]);

    foreach ([
        ['feature_key' => 'premium_tts', 'limit_value' => 1],
        ['feature_key' => 'ai_credits', 'limit_value' => $aiCredits],
    ] as $row) {
        PlanLimit::query()->create([
            'plan_id' => $plan->id,
            'feature_key' => $row['feature_key'],
            'limit_value' => $row['limit_value'],
        ]);
    }

    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    return $plan;
}

function seedWorkspaceAiAgentPlan(Company $company): Plan
{
    $plan = Plan::factory()->create(['price_monthly' => 99]);

    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'workspace_ai_agent',
        'limit_value' => 1,
    ]);

    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    return $plan;
}

/**
 * @return array{_captcha_token: string, _captcha_answer: int}
 */
function validMathCaptchaFields(string $context): array
{
    $captcha = app(\App\Services\MathCaptchaService::class)->generate($context);
    $decoded = base64_decode($captcha['token'], true);
    [$answer] = explode('|', (string) $decoded);

    return [
        '_captcha_token' => $captcha['token'],
        '_captcha_answer' => (int) $answer,
    ];
}
