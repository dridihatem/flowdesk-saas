<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAiChatService
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

    private const DEFAULT_MODEL = 'gpt-4o-mini';

    public function isConfigured(?LlmProviderConfig $config = null): bool
    {
        return $this->resolveConfig($config)->hasOpenAi();
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function suggest(string $mode, string $context, ?LlmProviderConfig $config = null): array
    {
        $config = $this->resolveConfig($config);
        $apiKey = $config->openaiApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = $config->openaiModel ?: self::DEFAULT_MODEL;
        $userContent = AiAssistantPrompts::user($mode, $context);

        /** @var Response $response */
        $response = Http::timeout(60)
            ->withToken($apiKey)
            ->acceptJson()
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => 2048,
                'messages' => [
                    ['role' => 'system', 'content' => AiAssistantPrompts::system()],
                    ['role' => 'user', 'content' => $userContent],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        $tokenFields = $this->usageFieldsFromOpenAiData(is_array($data) ? $data : []);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Empty response from OpenAI.');
        }

        return [
            'suggestion' => trim($text),
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
        $apiKey = $config->openaiApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = $config->openaiModel ?: self::DEFAULT_MODEL;

        /** @var Response $response */
        $response = Http::timeout(120)
            ->withToken($apiKey)
            ->acceptJson()
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        $tokenFields = $this->usageFieldsFromOpenAiData(is_array($data) ? $data : []);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Empty response from OpenAI.');
        }

        return [
            'suggestion' => trim($text),
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
        $apiKey = $config->openaiApiKey;
        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = $config->openaiModel ?: self::DEFAULT_MODEL;

        /** @var Response $response */
        $response = Http::timeout(120)
            ->withToken($apiKey)
            ->acceptJson()
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $user],
                            [
                                'type' => 'image_url',
                                'image_url' => ['url' => "data:{$mimeType};base64,{$base64}"],
                            ],
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI vision API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException($this->friendlyHttpError($response));
        }

        $data = $response->json();
        $tokenFields = $this->usageFieldsFromOpenAiData(is_array($data) ? $data : []);
        $text = $data['choices'][0]['message']['content'] ?? null;
        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Empty response from OpenAI.');
        }

        return [
            'suggestion' => trim($text),
            'model' => $model,
            ...$tokenFields,
        ];
    }

    /**
     * @return array{input_tokens: int, output_tokens: int, total_tokens: int}
     */
    private function usageFieldsFromOpenAiData(array $data): array
    {
        $u = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $in = (int) ($u['prompt_tokens'] ?? 0);
        $out = (int) ($u['completion_tokens'] ?? 0);
        $tot = (int) ($u['total_tokens'] ?? 0);
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
        $msg = is_array($json) && isset($json['error']['message']) ? (string) $json['error']['message'] : $response->body();

        return match ($status) {
            401 => 'OpenAI API rejected the key (401). Check the API key in platform settings.',
            429 => 'OpenAI rate limit reached. Try again shortly.',
            default => 'OpenAI request failed'.($msg !== '' ? ": {$msg}" : '.'),
        };
    }
}
