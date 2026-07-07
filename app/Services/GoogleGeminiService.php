<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleGeminiService
{
    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    private const DEFAULT_MODEL = 'gemini-2.0-flash';

    public function isConfigured(?LlmProviderConfig $config = null): bool
    {
        return $this->resolveConfig($config)->hasGoogle();
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function suggest(string $mode, string $context, ?LlmProviderConfig $config = null): array
    {
        $system = AiAssistantPrompts::system();
        $userContent = AiAssistantPrompts::user($mode, $context);

        return $this->generateWithSystem($system, $userContent, 2048, __METHOD__, false, $config);
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function completeMessages(string $system, string $user, int $maxTokens = 4096, ?LlmProviderConfig $config = null): array
    {
        return $this->generateWithSystem($system, $user, $maxTokens, __METHOD__, false, $config);
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function completeWithGoogleSearch(string $system, string $user, int $maxTokens = 4096, ?LlmProviderConfig $config = null): array
    {
        return $this->generateWithSystem($system, $user, $maxTokens, __METHOD__, true, $config);
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    private function generateWithSystem(string $system, string $user, int $maxOutputTokens, string $logContext, bool $withGoogleSearch = false, ?LlmProviderConfig $config = null): array
    {
        $config = $this->resolveConfig($config);
        $apiKey = $config->googleApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Google (Gemini) API key is not configured.');
        }

        $model = $config->geminiModel ?: self::DEFAULT_MODEL;
        $url = self::API_BASE.'/models/'.rawurlencode($model).':generateContent';

        $body = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $user]],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxOutputTokens,
            ],
        ];

        if ($withGoogleSearch) {
            $body['tools'] = [
                ['google_search' => (object) []],
            ];
        }

        $timeout = $maxOutputTokens > 3000 ? 120 : 60;

        try {
            /** @var Response $response */
            $response = Http::timeout($timeout)
                ->connectTimeout(15)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'content-type' => 'application/json',
                ])
                ->post($url, $body);
        } catch (ConnectionException $e) {
            Log::warning('Google Gemini connection error', [
                'context' => $logContext,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException(
                __('Could not reach Google Gemini (network or timeout). Check SSL/DNS, firewall, and try again.')
            );
        }

        if (! $response->successful()) {
            Log::warning('Google Gemini API error', [
                'context' => $logContext,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid response from Gemini (not JSON).');
        }
        $tokenFields = $this->usageFieldsFromGeminiData($data);
        $candidates = $data['candidates'] ?? null;
        if (! is_array($candidates) || $candidates === []) {
            $block = $data['promptFeedback'] ?? null;
            if (is_array($block) && isset($block['blockReason'])) {
                throw new RuntimeException('Gemini blocked the request: '.(string) $block['blockReason']);
            }
            throw new RuntimeException('Empty response from Gemini.');
        }

        $first = $candidates[0] ?? null;
        $parts = is_array($first) ? ($first['content']['parts'] ?? []) : [];
        $texts = [];
        if (is_array($parts)) {
            foreach ($parts as $p) {
                if (is_array($p) && isset($p['text'])) {
                    $texts[] = (string) $p['text'];
                }
            }
        }
        $text = trim(implode("\n", $texts));
        if ($text === '') {
            throw new RuntimeException('Empty response from Gemini.');
        }

        return [
            'suggestion' => $text,
            'model' => $model,
            ...$tokenFields,
        ];
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function completeWithDocument(string $system, string $user, string $base64, string $mimeType, int $maxTokens = 4096, ?LlmProviderConfig $config = null): array
    {
        $config = $this->resolveConfig($config);
        $apiKey = $config->googleApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Google (Gemini) API key is not configured.');
        }

        $model = $config->geminiModel ?: self::DEFAULT_MODEL;
        $url = self::API_BASE.'/models/'.rawurlencode($model).':generateContent';

        $body = [
            'systemInstruction' => [
                'parts' => [['text' => $system]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $user],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        try {
            /** @var Response $response */
            $response = Http::timeout(120)
                ->connectTimeout(15)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'content-type' => 'application/json',
                ])
                ->post($url, $body);
        } catch (ConnectionException $e) {
            Log::warning('Google Gemini vision connection error', [
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException(
                __('Could not reach Google Gemini (network or timeout). Check SSL/DNS, firewall, and try again.')
            );
        }

        if (! $response->successful()) {
            Log::warning('Google Gemini vision API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid response from Gemini (not JSON).');
        }
        $tokenFields = $this->usageFieldsFromGeminiData($data);
        $candidates = $data['candidates'] ?? null;
        if (! is_array($candidates) || $candidates === []) {
            $block = $data['promptFeedback'] ?? null;
            if (is_array($block) && isset($block['blockReason'])) {
                throw new RuntimeException('Gemini blocked the request: '.(string) $block['blockReason']);
            }
            throw new RuntimeException('Empty response from Gemini.');
        }

        $first = $candidates[0] ?? null;
        $parts = is_array($first) ? ($first['content']['parts'] ?? []) : [];
        $texts = [];
        if (is_array($parts)) {
            foreach ($parts as $p) {
                if (is_array($p) && isset($p['text'])) {
                    $texts[] = (string) $p['text'];
                }
            }
        }
        $text = trim(implode("\n", $texts));
        if ($text === '') {
            throw new RuntimeException('Empty response from Gemini.');
        }

        return [
            'suggestion' => $text,
            'model' => $model,
            ...$tokenFields,
        ];
    }

    /**
     * @return array{input_tokens: int, output_tokens: int, total_tokens: int}
     */
    private function usageFieldsFromGeminiData(array $data): array
    {
        $m = is_array($data['usageMetadata'] ?? null) ? $data['usageMetadata'] : [];
        $in = (int) ($m['promptTokenCount'] ?? 0);
        $out = (int) ($m['candidatesTokenCount'] ?? 0);
        $tot = (int) ($m['totalTokenCount'] ?? 0);
        if ($tot < 1) {
            $tot = $in + $out;
        }

        return [
            'input_tokens' => $in,
            'output_tokens' => $out,
            'total_tokens' => $tot,
        ];
    }

    private function resolveConfig(?LlmProviderConfig $config): LlmProviderConfig
    {
        return $config ?? app(WorkspaceAiConfigService::class)->platformConfig();
    }

    private function friendlyHttpError(Response $response): string
    {
        $status = $response->status();
        $json = $response->json();
        $msg = '';
        if (is_array($json)) {
            if (isset($json['error']['message'])) {
                $msg = (string) $json['error']['message'];
            } elseif (isset($json['error']['status'])) {
                $msg = (string) $json['error']['status'];
            }
        }
        if ($msg === '') {
            $msg = $response->body();
        }

        return match ($status) {
            400 => 'Gemini request failed'.($msg !== '' ? ": {$msg}" : '.'),
            401, 403 => 'Google (Gemini) API rejected the key (401/403). Check the API key from Google AI Studio in platform settings.',
            404 => 'Gemini model not found. Check the model name in platform settings.'.($msg !== '' ? " ({$msg})" : ''),
            429 => 'Gemini rate limit reached. Try again shortly.',
            503 => 'Gemini is temporarily unavailable. Try again shortly.',
            default => 'Gemini request failed'.($msg !== '' ? ": {$msg}" : '.'),
        };
    }
}
