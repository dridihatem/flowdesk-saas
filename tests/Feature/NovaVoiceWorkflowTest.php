<?php

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Services\NovaVoiceNavigationService;
use App\Services\NovaVoiceWorkflowService;
use App\Services\PlanLimitService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova voice nav config exposes voice workflows', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    $config = app(NovaVoiceNavigationService::class)->clientConfig($user, $gates);

    expect($config['workflowUrl'])->toBe(route('assistant.voice-workflow'));
    expect(collect($config['workflows'])->pluck('id'))->toContain('create_client', 'update_vat');
});

test('voice workflow creates client from spoken details', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $user->assignRole('company_admin');

    $service = app(NovaVoiceWorkflowService::class);

    $start = $service->start($user, 'create_client');
    expect($start['active'])->toBeTrue();
    expect($start['reply'])->toBe(__('nova_workflow_create_client_prompt'));

    $advance = $service->advance($user, 'John Smith john@acme.test phone 5551234567 no portal account');
    expect($advance['done'])->toBeTrue();
    expect($advance['redirect_url'])->toBe(route('clients.index'));

    $client = Client::query()->withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('email', 'john@acme.test')
        ->first();

    expect($client)->not->toBeNull();
    expect($client->name)->toBe('John Smith');
    expect($service->hasActiveSession($user))->toBeFalse();
});

test('voice workflow api creates client with portal account', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $user->assignRole('company_admin');

    $this->actingAs($user)
        ->postJson(route('assistant.voice-workflow'), [
            'action' => 'start',
            'workflow' => 'create_client',
        ])
        ->assertOk()
        ->assertJsonPath('active', true);

    $this->actingAs($user)
        ->postJson(route('assistant.voice-workflow'), [
            'action' => 'advance',
            'input' => 'Jane Doe jane@portal.test 0611223344 yes create account',
        ])
        ->assertOk()
        ->assertJsonPath('done', true);

    $client = Client::query()->withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->where('email', 'jane@portal.test')
        ->first();

    expect($client)->not->toBeNull();
    expect($client->user_id)->not->toBeNull();
});

test('voice workflow updates vat rate', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $user->assignRole('company_admin');

    $service = app(NovaVoiceWorkflowService::class);
    $service->start($user, 'update_vat');
    $result = $service->advance($user, 'set vat to 19 percent');

    expect($result['done'])->toBeTrue();
    expect($result['reply'])->toBe(__('nova_workflow_update_vat_success', ['rate' => 19.0]));

    $company->refresh();
    $settings = $company->settings;
    expect((float) ($settings->billing['vat_percent'] ?? 0))->toBe(19.0);
});

test('voice workflow can be cancelled', function () {
    $company = Company::factory()->create();
    $user = User::factory()->for($company)->create();
    $user->assignRole('company_admin');

    $service = app(NovaVoiceWorkflowService::class);
    $service->start($user, 'create_client');
    expect($service->hasActiveSession($user))->toBeTrue();

    $service->clearSession($user);
    expect($service->hasActiveSession($user))->toBeFalse();
    Cache::forget('nova_voice_workflow:'.$user->id);
});
