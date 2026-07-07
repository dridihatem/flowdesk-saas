<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\NovaVoiceBriefingService;
use App\Services\NovaVoiceNavigationService;
use App\Services\PlanLimitService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova voice briefing service builds narrative paragraphs', function () {
    $user = User::factory()->create();
    $company = $user->company;

    app()->setLocale('fr');

    $built = app(NovaVoiceBriefingService::class)->buildBriefing($company, $user);

    expect($built['paragraphs'])->not->toBeEmpty();
    expect($built['text'])->toContain('Bonjour');
    expect($built['text'])->toContain($company->name);
});

test('nova voice nav config includes briefing phrases and url', function () {
    $user = User::factory()->create();
    $gates = array_fill_keys(PlanLimitService::FEATURE_KEYS, true);

    app()->setLocale('fr');

    $config = app(NovaVoiceNavigationService::class)->clientConfig($user, $gates);

    expect($config['briefingUrl'])->toBe(route('assistant.briefing'));
    expect($config['briefingRedirectUrl'])->toBeNull();
    expect($config['briefingPhrases'])->toContain('donne moi une analyse complete');
    expect($config['briefingCreditCost'])->toBe(15);
});

test('nova briefing endpoint requires authentication', function () {
    $this->post(route('assistant.briefing'))
        ->assertRedirect();
});

test('nova briefing returns audio when openai tts is configured', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-briefing', 200, ['Content-Type' => 'audio/mpeg']),
    ]);

    PlatformSetting::query()->create([
        'openai_api_key_encrypted' => 'sk-test-key',
        'openai_tts_voice' => 'nova',
        'openai_tts_model' => 'tts-1-hd',
        'tts_provider' => 'openai',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.briefing'))
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-AI-Credits-Cost', '15');
});

test('nova briefing returns 503 when browser voice only is selected', function () {
    PlatformSetting::query()->create([
        'tts_provider' => 'browser',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.briefing'))
        ->assertStatus(503)
        ->assertJsonStructure(['message', 'fallback', 'text']);
});

test('nova briefing text replay uses cache without double charge', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-briefing', 200, ['Content-Type' => 'audio/mpeg']),
    ]);

    PlatformSetting::query()->create([
        'openai_api_key_encrypted' => 'sk-test-key',
        'openai_tts_voice' => 'nova',
        'openai_tts_model' => 'tts-1-hd',
        'tts_provider' => 'openai',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.briefing'))
        ->assertOk()
        ->assertHeader('X-AI-Credits-Cost', '15');

    $this->actingAs($user)
        ->postJson(route('assistant.briefing', ['text_only' => 1, 'replay' => 1]))
        ->assertOk()
        ->assertJsonPath('replay', true)
        ->assertJsonStructure(['text']);
});
