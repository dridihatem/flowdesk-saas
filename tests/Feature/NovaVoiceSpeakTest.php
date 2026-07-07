<?php

use App\Models\PlatformSetting;
use App\Models\UsageTracking;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('nova speak endpoint requires authentication', function () {
    $this->post(route('assistant.speak'), ['text' => 'Hello'])
        ->assertRedirect();
});

test('nova speak returns audio when openai tts is configured', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
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
        ->postJson(route('assistant.speak'), ['text' => 'Yes Alex, what would you like?'])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertHeader('X-AI-Credits-Cost', '5');
});

test('nova speak strips markdown before openai tts request', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
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
        ->postJson(route('assistant.speak'), ['text' => '**Revenus** ce mois: 5200 EUR'])
        ->assertOk();

    Http::assertSent(function (Request $request) {
        $input = $request->data()['input'] ?? '';

        return str_contains($request->url(), 'audio/speech')
            && $input === 'Revenus ce mois: 5200 EUR'
            && ! str_contains($input, '*');
    });
});

test('nova speak returns 503 when browser voice only is selected', function () {
    PlatformSetting::query()->create([
        'tts_provider' => 'browser',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Hello'])
        ->assertStatus(503)
        ->assertJson([
            'fallback' => 'browser',
            'code' => 'tts_unconfigured',
        ]);
});

test('nova speak falls back to edge when openai tts is selected without paid subscription', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
    ]);

    PlatformSetting::query()->create([
        'openai_api_key_encrypted' => 'sk-test-key',
        'openai_tts_voice' => 'nova',
        'tts_provider' => 'openai',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Hello'])
        ->assertOk()
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('nova speak falls back to edge when openai tts is selected without key', function () {
    PlatformSetting::query()->create([
        'tts_provider' => 'openai',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Hello'])
        ->assertOk()
        ->assertHeader('X-Nova-TTS-Provider', 'edge');
});

test('nova speak returns credit limit json with browser fallback', function () {
    Http::fake([
        'api.openai.com/v1/audio/speech' => Http::response('fake-mp3-bytes', 200, ['Content-Type' => 'audio/mpeg']),
    ]);

    PlatformSetting::query()->create([
        'openai_api_key_encrypted' => 'sk-test-key',
        'openai_tts_voice' => 'nova',
        'openai_tts_model' => 'tts-1-hd',
        'tts_provider' => 'openai',
    ]);

    $user = User::factory()->create();
    $company = $user->company;
    seedPaidPremiumTtsForCompany($company, 1);
    UsageTracking::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'metric' => 'ai_credits',
        'value' => 1,
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $this->actingAs($user)
        ->postJson(route('assistant.speak'), ['text' => 'Hello'])
        ->assertStatus(403)
        ->assertJson([
            'fallback' => 'browser',
            'code' => 'ai_credits_limit',
        ])
        ->assertJsonPath('message', __('ai_credits_insufficient', [
            'required' => 5,
            'remaining' => 0,
        ]));
});
