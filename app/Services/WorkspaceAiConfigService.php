<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\PlatformSetting;

class WorkspaceAiConfigService
{
    public function __construct(
        private PlanLimitService $planLimits,
    ) {}

    public function canConfigureWorkspaceAgent(Company $company): bool
    {
        return $this->planLimits->isFeatureEnabled($company, 'workspace_ai_agent');
    }

    public function usesWorkspaceAgent(Company $company): bool
    {
        if (! $this->canConfigureWorkspaceAgent($company)) {
            return false;
        }

        $settings = $this->settingsRow($company);
        $agent = is_array($settings?->ai_agent) ? $settings->ai_agent : [];

        if (! (bool) ($agent['enabled'] ?? false)) {
            return false;
        }

        return $this->hasAnyWorkspaceKey($settings);
    }

    public function isAvailable(?Company $company = null): bool
    {
        if ($company !== null && $this->usesWorkspaceAgent($company)) {
            return $this->resolve($company)->resolveProvider() !== null;
        }

        return $this->platformConfig()->resolveProvider() !== null;
    }

    public function unavailableMessage(?Company $company = null): string
    {
        if ($company !== null && $this->usesWorkspaceAgent($company)) {
            return __('Add your workspace AI API keys in Settings → AI agent and enable your own agent.');
        }

        return __('Add an OpenAI, Anthropic, or Google (Gemini) API key in platform settings (and choose the AI provider).');
    }

    public function resolve(?Company $company = null): LlmProviderConfig
    {
        if ($company !== null && $this->usesWorkspaceAgent($company)) {
            return $this->workspaceConfig($company);
        }

        return $this->platformConfig();
    }

    public function platformConfig(): LlmProviderConfig
    {
        $row = PlatformSetting::query()->first();

        return new LlmProviderConfig(
            source: 'platform',
            aiProvider: (string) ($row?->ai_provider ?? 'auto'),
            anthropicApiKey: $this->stringOrNull($row?->anthropic_api_key_encrypted),
            openaiApiKey: $this->stringOrNull($row?->openai_api_key_encrypted),
            googleApiKey: $this->stringOrNull($row?->google_api_key_encrypted),
            claudeModel: $this->stringOrNull($row?->claude_model),
            openaiModel: $this->stringOrNull($row?->openai_model),
            geminiModel: $this->stringOrNull($row?->gemini_model),
        );
    }

    public function workspaceConfig(Company $company): LlmProviderConfig
    {
        $settings = $this->settingsRow($company);
        $agent = is_array($settings?->ai_agent) ? $settings->ai_agent : [];

        return new LlmProviderConfig(
            source: 'workspace',
            aiProvider: (string) ($agent['ai_provider'] ?? 'auto'),
            anthropicApiKey: $this->stringOrNull($settings?->workspace_anthropic_api_key_encrypted),
            openaiApiKey: $this->stringOrNull($settings?->workspace_openai_api_key_encrypted),
            googleApiKey: $this->stringOrNull($settings?->workspace_google_api_key_encrypted),
            claudeModel: $this->stringOrNull($agent['claude_model'] ?? null),
            openaiModel: $this->stringOrNull($agent['openai_model'] ?? null),
            geminiModel: $this->stringOrNull($agent['gemini_model'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(Company $company): array
    {
        $settings = $this->settingsRow($company);
        $agent = is_array($settings?->ai_agent) ? $settings->ai_agent : [];

        return [
            'can_configure' => $this->canConfigureWorkspaceAgent($company),
            'enabled' => (bool) ($agent['enabled'] ?? false),
            'ai_provider' => (string) ($agent['ai_provider'] ?? 'auto'),
            'openai_model' => (string) ($agent['openai_model'] ?? ''),
            'claude_model' => (string) ($agent['claude_model'] ?? ''),
            'gemini_model' => (string) ($agent['gemini_model'] ?? ''),
            'has_openai_key' => filled($settings?->workspace_openai_api_key_encrypted),
            'has_anthropic_key' => filled($settings?->workspace_anthropic_api_key_encrypted),
            'has_google_key' => filled($settings?->workspace_google_api_key_encrypted),
            'uses_workspace_agent' => $this->usesWorkspaceAgent($company),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveFromRequest(Company $company, array $data): void
    {
        abort_unless($this->canConfigureWorkspaceAgent($company), 403, __('plan_feature_not_included'));

        $settings = $company->settings()->firstOrCreate();

        $agent = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'ai_provider' => in_array($data['ai_provider'] ?? 'auto', ['auto', 'anthropic', 'openai', 'google'], true)
                ? ($data['ai_provider'] ?? 'auto')
                : 'auto',
            'openai_model' => ($data['openai_model'] ?? '') === '' ? null : $data['openai_model'],
            'claude_model' => ($data['claude_model'] ?? '') === '' ? null : $data['claude_model'],
            'gemini_model' => ($data['gemini_model'] ?? '') === '' ? null : $data['gemini_model'],
        ];

        if (filled($data['openai_api_key'] ?? null)) {
            $settings->workspace_openai_api_key_encrypted = $data['openai_api_key'];
        } elseif (! empty($data['clear_openai_api_key'])) {
            $settings->workspace_openai_api_key_encrypted = null;
        }

        if (filled($data['anthropic_api_key'] ?? null)) {
            $settings->workspace_anthropic_api_key_encrypted = $data['anthropic_api_key'];
        } elseif (! empty($data['clear_anthropic_api_key'])) {
            $settings->workspace_anthropic_api_key_encrypted = null;
        }

        if (filled($data['google_api_key'] ?? null)) {
            $settings->workspace_google_api_key_encrypted = $data['google_api_key'];
        } elseif (! empty($data['clear_google_api_key'])) {
            $settings->workspace_google_api_key_encrypted = null;
        }

        if ($agent['enabled'] && ! $this->hasAnyWorkspaceKey($settings)) {
            $agent['enabled'] = false;
        }

        $settings->ai_agent = $agent;
        $settings->save();
    }

    private function settingsRow(Company $company): ?CompanySetting
    {
        return CompanySetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();
    }

    private function hasAnyWorkspaceKey(?CompanySetting $settings): bool
    {
        if ($settings === null) {
            return false;
        }

        return filled($settings->workspace_openai_api_key_encrypted)
            || filled($settings->workspace_anthropic_api_key_encrypted)
            || filled($settings->workspace_google_api_key_encrypted);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
