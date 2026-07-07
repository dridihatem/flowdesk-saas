<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AnthropicClaudeService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const DEFAULT_MODEL = 'claude-3-5-haiku-20241022';

    public function isConfigured(?LlmProviderConfig $config = null): bool
    {
        return $this->resolveConfig($config)->hasAnthropic();
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function suggest(string $mode, string $context, ?LlmProviderConfig $config = null): array
    {
        $config = $this->resolveConfig($config);
        $apiKey = $config->anthropicApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $model = $config->claudeModel ?: self::DEFAULT_MODEL;
        $userContent = AiAssistantPrompts::user($mode, $context);

        /** @var Response $response */
        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => 2048,
                'system' => AiAssistantPrompts::system(),
                'messages' => [
                    ['role' => 'user', 'content' => $userContent],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        $tokenFields = $this->usageFieldsFromAnthropicData(is_array($data) ? $data : []);
        $parts = [];
        foreach ($data['content'] ?? [] as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'text' && isset($block['text'])) {
                $parts[] = (string) $block['text'];
            }
        }
        $text = trim(implode("\n", $parts));
        if ($text === '') {
            throw new RuntimeException('Empty response from Claude.');
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
    public function completeMessages(string $system, string $user, int $maxTokens = 4096, ?LlmProviderConfig $config = null): array
    {
        $config = $this->resolveConfig($config);
        $apiKey = $config->anthropicApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $model = $config->claudeModel ?: self::DEFAULT_MODEL;

        /** @var Response $response */
        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        $tokenFields = $this->usageFieldsFromAnthropicData(is_array($data) ? $data : []);
        $parts = [];
        foreach ($data['content'] ?? [] as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'text' && isset($block['text'])) {
                $parts[] = (string) $block['text'];
            }
        }
        $text = trim(implode("\n", $parts));
        if ($text === '') {
            throw new RuntimeException('Empty response from Claude.');
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
        if ($mimeType === 'application/pdf') {
            throw new RuntimeException(__('PDF scanning requires Google (Gemini) as the AI provider.'));
        }

        $config = $this->resolveConfig($config);
        $apiKey = $config->anthropicApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $model = $config->claudeModel ?: self::DEFAULT_MODEL;

        /** @var Response $response */
        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $base64,
                                ],
                            ],
                            ['type' => 'text', 'text' => $user],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Anthropic vision API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        $tokenFields = $this->usageFieldsFromAnthropicData(is_array($data) ? $data : []);
        $parts = [];
        foreach ($data['content'] ?? [] as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'text' && isset($block['text'])) {
                $parts[] = (string) $block['text'];
            }
        }
        $text = trim(implode("\n", $parts));
        if ($text === '') {
            throw new RuntimeException('Empty response from Claude.');
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
    private function usageFieldsFromAnthropicData(array $data): array
    {
        $u = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $in = (int) ($u['input_tokens'] ?? 0);
        $out = (int) ($u['output_tokens'] ?? 0);
        $tot = $in + $out;

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
        $msg = is_array($json) && isset($json['error']['message']) ? (string) $json['error']['message'] : $response->body();

        return match ($status) {
            401 => 'Anthropic API rejected the key (401). Check the API key in platform settings.',
            429 => 'Claude rate limit reached. Try again shortly.',
            529 => 'Claude is temporarily overloaded. Try again shortly.',
            default => 'Claude request failed'.($msg !== '' ? ": {$msg}" : '.'),
        };
    }
}
