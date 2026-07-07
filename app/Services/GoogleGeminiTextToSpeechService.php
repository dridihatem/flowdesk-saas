<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleGeminiTextToSpeechService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    private const DEFAULT_MODEL = 'gemini-2.5-flash-preview-tts';

    private const DEFAULT_VOICE = 'Kore';

    /** @var list<string> */
    public const VOICES = [
        'Kore', 'Puck', 'Charon', 'Aoede', 'Fenrir', 'Leda', 'Orus', 'Zephyr',
        'Achird', 'Sulafat', 'Vindemiatrix', 'Despina', 'Enceladus', 'Iapetus',
    ];

    public function isConfigured(): bool
    {
        $row = PlatformSetting::query()->first();

        return $row && filled($row->google_api_key_encrypted);
    }

    /**
     * @return array{binary: string, mime: string, provider: string, model: string, voice: string}
     */
    public function synthesize(string $text, ?string $locale = null): array
    {
        $transcript = flowdesk_sanitize_speech_text($text);
        if ($transcript === '') {
            throw new RuntimeException('Speech text is empty.');
        }

        $row = PlatformSetting::query()->first();
        $apiKey = $row?->google_api_key_encrypted;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Google (Gemini) API key is not configured.');
        }

        $model = $this->normalizeModel($row?->gemini_tts_model);
        $voice = $this->normalizeVoice($row?->gemini_tts_voice);
        $languageHint = $this->languageHint($locale);

        $prompt = $languageHint !== ''
            ? "Say naturally in {$languageHint}: {$transcript}"
            : "Say naturally: {$transcript}";

        $url = self::API_BASE.'/models/'.rawurlencode($model).':generateContent';

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => [
                    'voiceConfig' => [
                        'prebuiltVoiceConfig' => [
                            'voiceName' => $voice,
                        ],
                    ],
                ],
            ],
        ];

        try {
            /** @var Response $response */
            $response = Http::timeout(90)
                ->connectTimeout(15)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'content-type' => 'application/json',
                ])
                ->post($url, $body);
        } catch (ConnectionException $e) {
            Log::warning('Gemini TTS connection error', ['message' => $e->getMessage()]);

            throw new RuntimeException(
                __('Could not reach Google Gemini (network or timeout). Check SSL/DNS, firewall, and try again.')
            );
        }

        if (! $response->successful()) {
            Log::warning('Gemini TTS API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid response from Gemini TTS.');
        }

        $pcm = $this->extractPcmFromResponse($data);
        if ($pcm === '') {
            throw new RuntimeException('Empty audio from Gemini TTS.');
        }

        return [
            'binary' => flowdesk_pcm16le_to_wav($pcm),
            'mime' => 'audio/wav',
            'provider' => 'google',
            'model' => $model,
            'voice' => $voice,
        ];
    }

    public function publicConfig(): array
    {
        $row = PlatformSetting::query()->first();

        return [
            'available' => $this->isConfigured(),
            'voice' => $this->normalizeVoice($row?->gemini_tts_voice),
            'model' => $this->normalizeModel($row?->gemini_tts_model),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractPcmFromResponse(array $data): string
    {
        $candidates = $data['candidates'] ?? null;
        if (! is_array($candidates) || $candidates === []) {
            return '';
        }

        $parts = $candidates[0]['content']['parts'] ?? [];
        if (! is_array($parts)) {
            return '';
        }

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
            if (! is_array($inline)) {
                continue;
            }

            $encoded = $inline['data'] ?? '';
            if (! is_string($encoded) || $encoded === '') {
                continue;
            }

            $binary = base64_decode($encoded, true);
            if ($binary === false || $binary === '') {
                continue;
            }

            $mime = strtolower((string) ($inline['mimeType'] ?? $inline['mime_type'] ?? ''));
            if (str_contains($mime, 'wav')) {
                return $binary;
            }

            return $binary;
        }

        return '';
    }

    private function normalizeModel(?string $model): string
    {
        $model = trim((string) $model);

        return $model !== '' ? $model : self::DEFAULT_MODEL;
    }

    private function normalizeVoice(?string $voice): string
    {
        $voice = trim((string) $voice);
        if ($voice === '') {
            return self::DEFAULT_VOICE;
        }

        foreach (self::VOICES as $allowed) {
            if (strcasecmp($allowed, $voice) === 0) {
                return $allowed;
            }
        }

        return self::DEFAULT_VOICE;
    }

    private function languageHint(?string $locale): string
    {
        return match (strtolower(substr((string) $locale, 0, 2))) {
            'fr' => 'French',
            'es' => 'Spanish',
            'ar' => 'Arabic',
            default => 'English',
        };
    }

    private function friendlyHttpError(Response $response): string
    {
        $json = $response->json();
        $msg = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';

        return match ($response->status()) {
            400 => __('Gemini TTS request failed').($msg !== '' ? ": {$msg}" : '.'),
            401, 403 => __('Google (Gemini) API rejected the key. Check your Google AI Studio key in platform settings.'),
            404 => __('Gemini TTS model not found. Check the TTS model name in platform settings.').($msg !== '' ? " ({$msg})" : ''),
            429 => __('Gemini TTS quota reached (free tier is limited). Microsoft Edge voice is used automatically when available, or enable billing in Google AI Studio and try again.'),
            503 => __('Gemini TTS is temporarily unavailable. Try again shortly.'),
            default => __('Gemini TTS request failed').($msg !== '' ? ": {$msg}" : '.'),
        };
    }
}
