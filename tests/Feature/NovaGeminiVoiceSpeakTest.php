<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\GoogleGeminiTextToSpeechService;
use App\Services\MicrosoftEdgeTextToSpeechService;
use App\Services\NovaTextToSpeechService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('gemini tts returns wav audio from pcm response', function () {
    $pcm = str_repeat("\0\0", 120);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'inlineData' => [
                            'mimeType' => 'audio/pcm',
                            'data' => base64_encode($pcm),
                        ],
                    ]],
                ],
            ]],
        ], 200),
    ]);

    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'gemini_tts_model' => 'gemini-2.5-flash-preview-tts',
        'gemini_tts_voice' => 'Kore',
    ]);

    $result = app(GoogleGeminiTextToSpeechService::class)->synthesize('Bonjour Alex');

    expect($result['mime'])->toBe('audio/wav');
    expect($result['provider'])->toBe('google');
    expect(substr($result['binary'], 0, 4))->toBe('RIFF');
});

test('nova speak uses gemini tts when google key is configured', function () {
    $pcm = str_repeat("\0\0", 80);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'inlineData' => [
                            'mimeType' => 'audio/pcm',
                            'data' => base64_encode($pcm),
                        ],
                    ]],
                ],
            ]],
        ], 200),
    ]);

    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'gemini_tts_model' => 'gemini-2.5-flash-preview-tts',
        'tts_provider' => 'google',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Yes Alex, what would you like?'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/wav')
        ->assertHeader('X-Nova-TTS-Provider', 'google');
});

test('nova speak falls back to edge when gemini tts is selected without paid subscription', function () {
    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'tts_provider' => 'google',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Hello'])
        ->assertOk()
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('nova tts router prefers google in auto mode when premium tts is available', function () {
    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'openai_api_key_encrypted' => 'sk-test',
        'tts_provider' => 'auto',
    ]);

    $company = \App\Models\Company::factory()->create();
    seedPaidPremiumTtsForCompany($company);

    expect(app(NovaTextToSpeechService::class)->resolveProvider(PlatformSetting::query()->first(), $company))
        ->toBe('google');
});

test('nova tts router prefers edge in auto mode without premium subscription', function () {
    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'openai_api_key_encrypted' => 'sk-test',
        'tts_provider' => 'auto',
    ]);

    expect(app(NovaTextToSpeechService::class)->resolveProvider(PlatformSetting::query()->first()))
        ->toBe('edge');
});

test('nova speak uses edge tts in auto mode', function () {
    $this->mock(MicrosoftEdgeTextToSpeechService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('publicConfig')->andReturn(['available' => true, 'voice' => 'fr-FR-DeniseNeural', 'locales' => []]);
        $mock->shouldReceive('synthesize')->once()->andReturn([
            'binary' => 'fake-edge-mp3',
            'mime' => 'audio/mpeg',
            'provider' => 'edge',
            'voice' => 'fr-FR-DeniseNeural',
        ]);
    });

    PlatformSetting::query()->create([
        'tts_provider' => 'auto',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Bonjour Alex'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('nova speak falls back to openai when gemini quota is reached in auto mode', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'Quota exceeded'],
        ], 429),
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3', 200, ['Content-Type' => 'audio/mpeg']),
    ]);

    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'openai_api_key_encrypted' => 'sk-test',
        'openai_tts_voice' => 'nova',
        'tts_provider' => 'auto',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Yes Alex, what would you like?'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-Nova-TTS-Provider', 'openai');
});

test('nova speak falls back to edge when gemini and openai rate limits are reached in auto mode', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'Quota exceeded'],
        ], 429),
        'api.openai.com/v1/audio/speech' => Http::response([
            'error' => ['message' => 'Rate limit exceeded'],
        ], 429),
    ]);

    $this->mock(MicrosoftEdgeTextToSpeechService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('publicConfig')->andReturn(['available' => true, 'voice' => 'en-US-JennyNeural', 'locales' => []]);
        $mock->shouldReceive('synthesize')->once()->andReturn([
            'binary' => 'fake-edge-mp3',
            'mime' => 'audio/mpeg',
            'provider' => 'edge',
            'voice' => 'en-US-JennyNeural',
        ]);
    });

    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'openai_api_key_encrypted' => 'sk-test',
        'openai_tts_voice' => 'nova',
        'tts_provider' => 'auto',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Yes Alex, what would you like?'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('nova speak falls back to edge when gemini quota is reached with google preference', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'Quota exceeded'],
        ], 429),
    ]);

    $this->mock(MicrosoftEdgeTextToSpeechService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('publicConfig')->andReturn(['available' => true, 'voice' => 'fr-FR-DeniseNeural', 'locales' => []]);
        $mock->shouldReceive('synthesize')->once()->andReturn([
            'binary' => 'fake-edge-mp3',
            'mime' => 'audio/mpeg',
            'provider' => 'edge',
            'voice' => 'fr-FR-DeniseNeural',
        ]);
    });

    PlatformSetting::query()->create([
        'google_api_key_encrypted' => 'google-test-key',
        'gemini_tts_model' => 'gemini-2.5-flash-preview-tts',
        'tts_provider' => 'google',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Bonjour Alex'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('nova speak falls back to edge when openai rate limit is reached with openai preference', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response([
            'error' => ['message' => 'Rate limit exceeded'],
        ], 429),
    ]);

    $this->mock(MicrosoftEdgeTextToSpeechService::class, function ($mock) {
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('publicConfig')->andReturn(['available' => true, 'voice' => 'en-US-JennyNeural', 'locales' => []]);
        $mock->shouldReceive('synthesize')->once()->andReturn([
            'binary' => 'fake-edge-mp3',
            'mime' => 'audio/mpeg',
            'provider' => 'edge',
            'voice' => 'en-US-JennyNeural',
        ]);
    });

    PlatformSetting::query()->create([
        'openai_api_key_encrypted' => 'sk-test-key',
        'openai_tts_voice' => 'nova',
        'tts_provider' => 'openai',
    ]);

    $user = User::factory()->create();
    seedPaidPremiumTtsForCompany($user->company);

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Yes Alex, what would you like?'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('edge tts resolves locale voices', function () {
    $service = app(MicrosoftEdgeTextToSpeechService::class);

    expect($service->resolveVoice('fr', null))->toBe('fr-FR-DeniseNeural');
    expect($service->resolveVoice('en', null))->toBe('en-US-JennyNeural');
    expect($service->resolveVoice('es', null))->toBe('es-ES-ElviraNeural');
    expect($service->resolveVoice('ar', null))->toBe('ar-EG-SalmaNeural');
});
