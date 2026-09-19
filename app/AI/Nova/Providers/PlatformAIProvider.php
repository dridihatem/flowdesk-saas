<?php

namespace App\AI\Nova\Providers;

use App\Models\Company;
use App\Services\PlatformLlmRouter;

/**
 * Default Nova AI provider — wraps the existing FlowDesk PlatformLlmRouter
 * (OpenAI / Anthropic / Gemini) so Nova does not hardcode a single vendor.
 */
class PlatformAIProvider implements AIProvider
{
    public function __construct(private PlatformLlmRouter $router) {}

    public function isAvailable(?Company $company = null): bool
    {
        return $this->router->isAvailable($company);
    }

    public function chat(string $system, string $user, int $maxTokens = 4096, ?Company $company = null): array
    {
        return $this->router->complete($system, $user, $maxTokens, $company);
    }

    public function generate(string $prompt, int $maxTokens = 2048, ?Company $company = null): string
    {
        $result = $this->router->complete(
            'You are Nova, a helpful business assistant. Reply with the requested content only.',
            $prompt,
            $maxTokens,
            $company
        );

        return (string) ($result['suggestion'] ?? '');
    }

    public function toolCall(string $system, string $user, array $tools, ?Company $company = null): string
    {
        $result = $this->router->complete($system, $user, 2048, $company);
        $text = trim((string) ($result['suggestion'] ?? ''));
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
            $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        }

        return $text;
    }

    public function analyzeDocument(string $system, string $user, string $base64, string $mimeType, int $maxTokens = 4096, ?Company $company = null): array
    {
        return $this->router->completeWithDocument($system, $user, $base64, $mimeType, $maxTokens, $company);
    }
}
