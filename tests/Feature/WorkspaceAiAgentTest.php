<?php

use App\Models\CompanySetting;
use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlatformLlmRouter;
use App\Services\WorkspaceAiConfigService;
use Illuminate\Support\Facades\Http;

test('workspace ai agent settings blocked without plan feature', function () {
    $plan = Plan::factory()->create();
    PlanLimit::query()->create([
        'plan_id' => $plan->id,
        'feature_key' => 'workspace_ai_agent',
        'limit_value' => 0,
    ]);
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    Subscription::factory()->create([
        'company_id' => $user->company_id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get(route('settings.ai-agent'))
        ->assertForbidden();
});

test('company admin can save workspace ai agent on eligible plan', function () {
    $user = User::factory()->create();
    $user->syncRoles(['company_admin']);
    seedWorkspaceAiAgentPlan($user->company);

    $this->actingAs($user)
        ->put(route('settings.ai-agent.update'), [
            'enabled' => '1',
            'ai_provider' => 'openai',
            'openai_api_key' => 'sk-workspace-test-key',
            'openai_model' => 'gpt-4o-mini',
        ])
        ->assertRedirect(route('settings.ai-agent'));

    $company = $user->company->fresh();
    expect(app(WorkspaceAiConfigService::class)->usesWorkspaceAgent($company))->toBeTrue();
});

test('platform llm router uses workspace openai key when workspace agent enabled', function () {
    PlatformSetting::query()->delete();
    PlatformSetting::query()->create([
        'ai_provider' => 'openai',
        'openai_api_key_encrypted' => 'sk-platform-key',
        'openai_model' => 'gpt-4o-mini',
    ]);

    $user = User::factory()->create();
    seedWorkspaceAiAgentPlan($user->company);

    $settings = CompanySetting::query()
        ->withoutGlobalScopes()
        ->firstOrCreate(['company_id' => $user->company_id]);
    $settings->ai_agent = [
        'enabled' => true,
        'ai_provider' => 'openai',
        'openai_model' => 'gpt-4o-mini',
        'claude_model' => null,
        'gemini_model' => null,
    ];
    $settings->workspace_openai_api_key_encrypted = 'sk-workspace-key';
    $settings->save();

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Hello from workspace']],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ]),
    ]);

    $result = app(PlatformLlmRouter::class)->complete('You are helpful.', 'Hi', 256, $user->company);

    expect($result['suggestion'])->toBe('Hello from workspace');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer sk-workspace-key');
    });
});

test('platform llm router uses platform key when workspace agent disabled', function () {
    PlatformSetting::query()->delete();
    PlatformSetting::query()->create([
        'ai_provider' => 'openai',
        'openai_api_key_encrypted' => 'sk-platform-key',
        'openai_model' => 'gpt-4o-mini',
    ]);

    $user = User::factory()->create();

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Hello from platform']],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ]),
    ]);

    $result = app(PlatformLlmRouter::class)->complete('You are helpful.', 'Hi', 256, $user->company);

    expect($result['suggestion'])->toBe('Hello from platform');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer sk-platform-key');
    });
});
