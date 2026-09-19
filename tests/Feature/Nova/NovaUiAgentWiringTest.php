<?php

use App\Models\Client;
use App\Models\User;
use App\Support\NovaPageContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova page context resolves route name and entity from request', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_id' => $user->company_id]);

    $request = Request::create('/clients/'.$client->id, 'GET');
    $route = new Route(['GET'], '/clients/{client}', fn () => null);
    $route->name('clients.show');
    $route->bind($request);
    $route->setParameter('client', $client);
    $request->setRouteResolver(fn () => $route);

    $payload = NovaPageContext::clientPayload($request);

    expect($payload['current_page'])->toBe('clients.show')
        ->and($payload['current_entity'])->toBe([
            'type' => 'client',
            'id' => (string) $client->id,
        ]);
});

test('nova assistant page exposes agent orchestrator url', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('assistant.index'))
        ->assertOk()
        ->getContent();

    // Alpine @js() embeds JSON (often with \u0022 / \/ escapes) inside x-data.
    expect($html)->toContain('agentUrl')
        ->and($html)->toContain('useAgent')
        ->and($html)->toContain('currentPage')
        ->and($html)->toContain('legacyChatUrl')
        ->and($html)->toContain('assistant')
        ->and($html)->toContain('agent');
});

test('nova agent run accepts page entity context from current flowqil page', function () {
    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);
    $client = Client::factory()->create([
        'company_id' => $user->company_id,
        'name' => 'Context Corp',
    ]);

    $this->actingAs($user)
        ->postJson(route('assistant.agent.run'), [
            'message' => 'Find client Context',
            'current_page' => 'clients.show',
            'current_entity' => [
                'type' => 'client',
                'id' => (string) $client->id,
            ],
        ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonStructure(['message', 'run_id', 'activities', 'conversation_id']);
});

test('nova voice nav config prefers agent url while keeping legacy chat', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(\App\Services\PlanLimitService::FEATURE_KEYS, true);

    $config = app(\App\Services\NovaVoiceNavigationService::class)->clientConfig($user, $gates);

    expect($config['agentUrl'])->toBe(route('assistant.agent.run'))
        ->and($config['legacyChatUrl'])->toBe(route('assistant.chat'))
        ->and($config['chatUrl'])->toBe(route('assistant.chat'))
        ->and($config['useAgent'])->toBeTrue()
        ->and($config)->toHaveKeys(['currentPage', 'currentEntity', 'companyId']);
});

test('nova autopilot remains gated after ui wiring', function () {
    expect(config('flowdesk.nova_autopilot_enabled'))->toBeFalse();

    $user = User::factory()->create();
    $gate = app(\App\AI\Nova\Autopilot\AutopilotGate::class);
    $context = app(\App\AI\Nova\Memory\ContextBuilder::class)->build($user, $user->company);

    expect($gate->status($context)['safe_to_run'])->toBeFalse();
});
