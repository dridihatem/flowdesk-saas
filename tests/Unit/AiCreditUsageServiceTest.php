<?php

use App\Models\Company;
use App\Services\AiCreditUsageService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('credits for task uses flat config per task', function () {
    config(['flowdesk.ai_task_credits' => [
        'assistant' => [
            'default' => 50,
            'modes' => [
                'report_counsel' => 100,
                'project_description' => 80,
            ],
        ],
        'project_example_workspace' => 250,
        'report_counsel' => 100,
    ]]);

    $svc = app(AiCreditUsageService::class);

    expect($svc->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'report_counsel'))->toBe(100);
    expect($svc->creditsForTask(AiCreditUsageService::TASK_ASSISTANT, 'unknown_mode'))->toBe(50);
    expect($svc->creditsForTask(AiCreditUsageService::TASK_PROJECT_EXAMPLE))->toBe(250);
    expect($svc->creditsForTask(AiCreditUsageService::TASK_REPORT_COUNSEL))->toBe(100);
});

test('record for task writes configured credits to usage', function () {
    config(['flowdesk.ai_task_credits' => [
        'report_counsel' => 100,
    ]]);

    $company = Company::factory()->create();
    $svc = app(AiCreditUsageService::class);

    $charged = $svc->recordForTask($company, AiCreditUsageService::TASK_REPORT_COUNSEL);

    expect($charged)->toBe(100);
    expect($svc->usedThisMonth($company))->toBe(100);
});
