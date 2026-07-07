<?php

namespace App\Services;

use Afaya\EdgeTTS\Service\EdgeTTS;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MicrosoftEdgeTextToSpeechService
{
    /** @var list<string> */
    public const VOICES = [
        'fr-FR-DeniseNeural',
        'en-US-JennyNeural',
        'es-ES-ElviraNeural',
        'ar-EG-SalmaNeural',
        'ar-SA-ZariyahNeural',
        'fr-CA-SylvieNeural',
        'en-GB-SoniaNeural',
    ];

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * @return array{binary: string, mime: string, provider: string, voice: string}
     */
    public function synthesize(string $text, ?string $locale = null): array
    {
        $transcript = flowdesk_sanitize_speech_text($text);
        if ($transcript === '') {
            throw new RuntimeException('Speech text is empty.');
        }

        $settings = PlatformSetting::query()->first();
        $voice = $this->resolveVoice($locale, $settings);
        $binary = '';
        $lastError = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $tts = new EdgeTTS;
                $tts->synthesize(mb_substr($transcript, 0, 4096), $voice);
                $binary = $tts->toRaw();
                break;
            } catch (Throwable $e) {
                $lastError = $e;
                if ($attempt >= 2) {
                    Log::warning('Microsoft Edge TTS error', ['message' => $e->getMessage(), 'attempt' => $attempt]);

                    throw new RuntimeException(__('Microsoft Edge TTS is temporarily unavailable. Try again shortly or choose another voice provider.'));
                }

                usleep(250_000);
            }
        }

        if ($binary === '') {
            throw new RuntimeException('Empty audio from Microsoft Edge TTS.');
        }

        return [
            'binary' => $binary,
            'mime' => 'audio/mpeg',
            'provider' => 'edge',
            'voice' => $voice,
        ];
    }

    public function publicConfig(): array
    {
        $row = PlatformSetting::query()->first();

        return [
            'available' => true,
            'voice' => $this->resolveVoice(app()->getLocale(), $row),
            'locales' => config('flowdesk.nova_tts_voices.edge', []),
        ];
    }

    public function resolveVoice(?string $locale, ?PlatformSetting $settings): string
    {
        $override = trim((string) ($settings?->edge_tts_voice ?? ''));
        if ($override !== '') {
            foreach (self::VOICES as $allowed) {
                if (strcasecmp($allowed, $override) === 0) {
                    return $allowed;
                }
            }

            if (preg_match('/^[a-z]{2}-[A-Z]{2}-[A-Za-z]+Neural$/', $override) === 1) {
                return $override;
            }
        }

        $lang = strtolower(substr((string) $locale, 0, 2));
        $voices = config('flowdesk.nova_tts_voices.edge', []);
        if (! is_array($voices)) {
            $voices = [];
        }

        $voice = $voices[$lang] ?? $voices['en'] ?? 'en-US-JennyNeural';

        return is_string($voice) && $voice !== '' ? $voice : 'en-US-JennyNeural';
    }
}
