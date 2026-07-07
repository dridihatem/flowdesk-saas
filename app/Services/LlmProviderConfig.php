<?php

namespace App\Services;

class LlmProviderConfig
{
    public function __construct(
        public readonly string $source,
        public readonly string $aiProvider,
        public readonly ?string $anthropicApiKey,
        public readonly ?string $openaiApiKey,
        public readonly ?string $googleApiKey,
        public readonly ?string $claudeModel,
        public readonly ?string $openaiModel,
        public readonly ?string $geminiModel,
    ) {}

    public function usesWorkspaceKeys(): bool
    {
        return $this->source === 'workspace';
    }

    public function hasAnthropic(): bool
    {
        return is_string($this->anthropicApiKey) && $this->anthropicApiKey !== '';
    }

    public function hasOpenAi(): bool
    {
        return is_string($this->openaiApiKey) && $this->openaiApiKey !== '';
    }

    public function hasGoogle(): bool
    {
        return is_string($this->googleApiKey) && $this->googleApiKey !== '';
    }

    /**
     * @return 'anthropic'|'openai'|'google'|null
     */
    public function resolveProvider(): ?string
    {
        return match ($this->aiProvider) {
            'anthropic' => $this->hasAnthropic() ? 'anthropic' : null,
            'openai' => $this->hasOpenAi() ? 'openai' : null,
            'google' => $this->hasGoogle() ? 'google' : null,
            default => $this->hasAnthropic()
                ? 'anthropic'
                : ($this->hasOpenAi()
                    ? 'openai'
                    : ($this->hasGoogle() ? 'google' : null)),
        };
    }
}
