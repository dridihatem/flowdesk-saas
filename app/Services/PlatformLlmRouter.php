<?php

namespace App\Services;

use App\Models\Company;
use RuntimeException;

class PlatformLlmRouter
{
    public function __construct(
        private AnthropicClaudeService $anthropic,
        private OpenAiChatService $openai,
        private GoogleGeminiService $google,
        private WorkspaceAiConfigService $workspaceAi,
    ) {}

    public function isAvailable(?Company $company = null): bool
    {
        return $this->workspaceAi->isAvailable($company);
    }

    /**
     * @return 'anthropic'|'openai'|'google'|null
     */
    public function resolveProvider(?Company $company = null): ?string
    {
        return $this->workspaceAi->resolve($company)->resolveProvider();
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function complete(string $system, string $user, int $maxTokens = 4096, ?Company $company = null): array
    {
        $config = $this->workspaceAi->resolve($company);
        $provider = $config->resolveProvider();
        if ($provider === null) {
            throw new RuntimeException($this->missingKeysMessage($config));
        }

        return match ($provider) {
            'anthropic' => $this->anthropic->completeMessages($system, $user, $maxTokens, $config),
            'openai' => $this->openai->completeMessages($system, $user, $maxTokens, $config),
            'google' => $this->google->completeMessages($system, $user, $maxTokens, $config),
            default => throw new RuntimeException('Unknown AI provider.'),
        };
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int, used_web_search: bool}
     */
    public function completeWithWebAwareness(string $system, string $user, string $webSnippets, int $maxTokens = 2048, ?Company $company = null): array
    {
        $config = $this->workspaceAi->resolve($company);

        if ($config->hasGoogle()) {
            try {
                $result = $this->google->completeWithGoogleSearch($system, $user, $maxTokens, $config);

                return [...$result, 'used_web_search' => true];
            } catch (RuntimeException) {
                // Fall through to snippet-augmented completion.
            }
        }

        $augmentedUser = $user;
        if (trim($webSnippets) !== '') {
            $augmentedUser = "=== Web search snippets ===\n".$webSnippets."\n\n".$user;
        }

        return [...$this->complete($system, $augmentedUser, $maxTokens, $company), 'used_web_search' => trim($webSnippets) !== ''];
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function completeWithDocument(string $system, string $user, string $base64, string $mimeType, int $maxTokens = 4096, ?Company $company = null): array
    {
        $config = $this->workspaceAi->resolve($company);
        $mimeType = strtolower($mimeType);

        if ($mimeType === 'application/pdf') {
            if (! $config->hasGoogle()) {
                throw new RuntimeException(__('PDF scanning requires a Google (Gemini) API key.'));
            }
            $provider = 'google';
        } else {
            $provider = $config->resolveProvider();
            if ($provider === null) {
                throw new RuntimeException($this->missingKeysMessage($config));
            }
        }

        return match ($provider) {
            'anthropic' => $this->anthropic->completeWithDocument($system, $user, $base64, $mimeType, $maxTokens, $config),
            'openai' => $this->openai->completeWithDocument($system, $user, $base64, $mimeType, $maxTokens, $config),
            'google' => $this->google->completeWithDocument($system, $user, $base64, $mimeType, $maxTokens, $config),
            default => throw new RuntimeException('Unknown AI provider.'),
        };
    }

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function suggest(string $mode, string $context, ?Company $company = null): array
    {
        $config = $this->workspaceAi->resolve($company);
        $provider = $config->resolveProvider();
        if ($provider === null) {
            throw new RuntimeException($this->missingKeysMessage($config));
        }

        return match ($provider) {
            'anthropic' => $this->anthropic->suggest($mode, $context, $config),
            'openai' => $this->openai->suggest($mode, $context, $config),
            'google' => $this->google->suggest($mode, $context, $config),
            default => throw new RuntimeException('Unknown AI provider.'),
        };
    }

    private function missingKeysMessage(LlmProviderConfig $config): string
    {
        if ($config->usesWorkspaceKeys()) {
            return __('Add your workspace AI API keys in Settings → AI agent and enable your own agent.');
        }

        return __('Add an OpenAI, Anthropic, or Google (Gemini) API key in platform settings (and choose the AI provider).');
    }
}
