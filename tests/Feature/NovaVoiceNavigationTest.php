<?php

use App\Models\User;
use App\Services\NovaVoiceNavigationService;
use App\Services\PlanLimitService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova voice nav config includes wake reply hello and listening labels', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    $config = app(NovaVoiceNavigationService::class)->clientConfig($user, $gates);

    expect($config['userId'])->toBe((string) $user->id);
    expect($config['labels']['wakeReplyHello'])->toBe(__('nova_voice_wake_reply_hello', ['name' => 'Jane']));
    expect($config['labels']['wakeReplyListening'])->toBe(__('nova_voice_wake_reply_listening'));
});

test('nova voice nav config includes invoice commands for authorized user', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    $config = app(NovaVoiceNavigationService::class)->clientConfig($user, $gates);

    expect($config['enabled'])->toBeTrue();
    expect($config['brand'])->toBe('Nova');
    expect($config['chatUrl'])->toBe(route('assistant.chat'));
    expect($config['labels']['chatListening'])->toBe(__('nova_voice_chat_listening'));
    expect($config['labels']['alwaysOn'])->toBe(__('nova_voice_toujours_activee', ['name' => 'Nova']));

    $ids = collect($config['commands'])->pluck('id')->all();
    expect($ids)->toContain(
        'invoices.index',
        'invoices.create',
        'clients.create',
        'providers.create',
        'clients.account-requests.index',
        'settings.workspace',
        'projects.index',
        'reports.index',
        'email-marketing.audiences.index',
        'forms.create',
        'logout',
    );
});

test('nova voice logout command includes logout phrases', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    $commands = app(NovaVoiceNavigationService::class)->commandsFor($user, $gates);

    $logout = collect($commands)->firstWhere('id', 'logout');
    expect($logout)->not->toBeNull();
    expect($logout['action'] ?? null)->toBe('logout');
    expect($logout['phrases'])->toContain('logout');
    expect($logout['phrases'])->toContain('deconnexion');
});

test('nova voice phrases match french create actions', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    app()->setLocale('fr');

    $commands = app(NovaVoiceNavigationService::class)->commandsFor($user, $gates);

    $providerCreate = collect($commands)->firstWhere('id', 'providers.create');
    expect($providerCreate)->not->toBeNull();
    expect($providerCreate['phrases'])->toContain('ajouter un fournisseur');

    $clientRequests = collect($commands)->firstWhere('id', 'clients.account-requests.index');
    expect($clientRequests)->not->toBeNull();
    expect($clientRequests['phrases'])->toContain('demande d inscription');
});

test('nova voice create invoice url enables ai dictation', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    $commands = app(NovaVoiceNavigationService::class)->commandsFor($user, $gates);

    $invoiceCreate = collect($commands)->firstWhere('id', 'invoices.create');
    expect($invoiceCreate)->not->toBeNull();
    expect($invoiceCreate['url'])->toContain('nova_ai=1');

    $proposalCreate = collect($commands)->firstWhere('id', 'proposals.create');
    expect($proposalCreate)->not->toBeNull();
    expect($proposalCreate['url'])->toContain('nova_ai=1');
});

test('workspace layout exposes nova voice nav widget when ai credits enabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('novaVoiceNav', false);
});
