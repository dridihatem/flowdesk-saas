<?php

namespace App\AI\Nova\Providers;

use App\Models\Company;
use App\Services\OpenAiChatService;
use App\Services\WorkspaceAiConfigService;
use RuntimeException;

class OpenAIProvider implements AIProvider
{
    public function __construct(
        private OpenAiChatService $openai,
        private WorkspaceAiConfigService $workspaceAi,
    ) {}

    public function isAvailable(?Company $company = null): bool
    {
        return $this->workspaceAi->resolve($company)->hasOpenAi();
    }

    public function chat(string $system, string $user, int $maxTokens = 4096, ?Company $company = null): array
    {
        $config = $this->workspaceAi->resolve($company);
        if (! $config->hasOpenAi()) {
            throw new RuntimeException('OpenAI is not configured.');
        }

        return $this->openai->completeMessages($system, $user, $maxTokens, $config);
    }

    public function generate(string $prompt, int $maxTokens = 2048, ?Company $company = null): string
    {
        return (string) ($this->chat('You are Nova.', $prompt, $maxTokens, $company)['suggestion'] ?? '');
    }

    public function toolCall(string $system, string $user, array $tools, ?Company $company = null): string
    {
        return trim((string) ($this->chat($system, $user, 2048, $company)['suggestion'] ?? ''));
    }

    public function analyzeDocument(string $system, string $user, string $base64, string $mimeType, int $maxTokens = 4096, ?Company $company = null): array
    {
        $config = $this->workspaceAi->resolve($company);

        return $this->openai->completeWithDocument($system, $user, $base64, $mimeType, $maxTokens, $config);
    }
}
