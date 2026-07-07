<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NovaTextToSpeechService
{
    public function __construct(
        private MicrosoftEdgeTextToSpeechService $edge,
        private GoogleGeminiTextToSpeechService $gemini,
        private OpenAiTextToSpeechService $openai,
        private PlanLimitService $planLimits,
    ) {}

    public function isConfigured(?Company $company = null): bool
    {
        return $this->resolveProvider(PlatformSetting::query()->first(), $company) !== null;
    }

    /**
     * @return array{binary: string, mime: string, provider: string, model?: string, voice?: string}
     */
    public function synthesize(string $text, ?string $locale = null, ?Company $company = null): array
    {
        $settings = PlatformSetting::query()->first();
        $preference = $settings?->tts_provider ?? 'auto';
        $chain = $this->providerChain($preference, $settings, $company);

        if ($chain === [] && in_array($preference, ['google', 'openai'], true)) {
            throw new RuntimeException($this->premiumTtsUnavailableMessage($preference, $company));
        }

        $lastError = null;
        $chainCount = count($chain);
        foreach ($chain as $index => $provider) {
            try {
                return match ($provider) {
                    'edge' => $this->edge->synthesize($text, $locale),
                    'google' => $this->gemini->synthesize($text, $locale),
                    'openai' => $this->wrapOpenAi($this->openai->synthesize($text, $locale)),
                    default => throw new RuntimeException('Unknown TTS provider.'),
                };
            } catch (RuntimeException $e) {
                $lastError = $e;

                if ($index >= $chainCount - 1) {
                    throw $e;
                }

                Log::info('Nova TTS: provider unavailable, trying next.', [
                    'provider' => $provider,
                    'preference' => $preference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw $lastError ?? new RuntimeException(__('Configure a voice provider in platform settings to enable Nova voice.'));
    }

    /**
     * @return array{provider: string|null, available: bool, server: bool, premium_tts: bool, edge: array<string, mixed>, gemini: array<string, mixed>, openai: array<string, mixed>}
     */
    public function publicConfig(?Company $company = null): array
    {
        $row = PlatformSetting::query()->first();
        $preference = $row?->tts_provider ?? 'auto';
        $provider = $this->resolveProvider($row, $company);
        $premiumTts = $company !== null && $this->planLimits->allowsPremiumTts($company);

        return [
            'provider' => $preference === 'browser' ? 'browser' : $provider,
            'available' => $provider !== null || $preference === 'browser',
            'server' => $provider !== null,
            'premium_tts' => $premiumTts,
            'edge' => $this->edge->publicConfig(),
            'gemini' => array_merge($this->gemini->publicConfig(), [
                'eligible' => $premiumTts && $this->gemini->isConfigured(),
            ]),
            'openai' => array_merge($this->openai->publicConfig(), [
                'eligible' => $premiumTts && $this->openai->isConfigured(),
            ]),
        ];
    }

    /**
     * @return 'edge'|'google'|'openai'|null
     */
    public function resolveProvider(?PlatformSetting $settings, ?Company $company = null): ?string
    {
        $preference = $settings?->tts_provider ?? 'auto';

        if ($preference === 'browser') {
            return null;
        }

        $chain = $this->providerChain($preference, $settings, $company);

        return $chain[0] ?? null;
    }

    /**
     * @return list<'edge'|'google'|'openai'>
     */
    private function providerChain(string $preference, ?PlatformSetting $settings, ?Company $company = null): array
    {
        $chain = match ($preference) {
            'edge' => ['edge'],
            'google' => ['google', 'edge'],
            'openai' => ['openai', 'edge'],
            'browser' => [],
            default => ['google', 'openai', 'edge'],
        };

        return array_values(array_filter($chain, fn (string $provider): bool => $this->providerIsAvailable($provider, $settings, $company)));
    }

    private function providerIsAvailable(string $provider, ?PlatformSetting $settings, ?Company $company = null): bool
    {
        return match ($provider) {
            'edge' => $this->edge->isConfigured(),
            'google' => $this->gemini->isConfigured() && $company !== null && $this->planLimits->allowsPremiumTts($company),
            'openai' => $this->openai->isConfigured() && $company !== null && $this->planLimits->allowsPremiumTts($company),
            default => false,
        };
    }

    private function premiumTtsUnavailableMessage(string $preference, ?Company $company): string
    {
        if ($company === null) {
            return __('Premium Nova voice requires a workspace with an active paid subscription.');
        }

        if (! $this->planLimits->allowsPremiumTts($company)) {
            return __('Premium Nova voice (Gemini/OpenAI TTS) requires an active paid subscription. Use Automatic or Microsoft Edge for free voice.');
        }

        return match ($preference) {
            'google' => __('Add a Google AI API key in platform settings to use Gemini TTS.'),
            'openai' => __('Add an OpenAI API key in platform settings to use OpenAI TTS.'),
            default => __('Premium Nova voice is not available for this workspace.'),
        };
    }

    /**
     * @return array{binary: string, mime: string, provider: string, model: string, voice: string}
     */
    private function wrapOpenAi(string $binary): array
    {
        $config = $this->openai->publicConfig();

        return [
            'binary' => $binary,
            'mime' => 'audio/mpeg',
            'provider' => 'openai',
            'model' => (string) ($config['model'] ?? ''),
            'voice' => (string) ($config['voice'] ?? ''),
        ];
    }
}
