<?php

namespace App\AI\Nova\Providers;

use App\Models\Company;

interface AIProvider
{
    public function isAvailable(?Company $company = null): bool;

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function chat(string $system, string $user, int $maxTokens = 4096, ?Company $company = null): array;

    public function generate(string $prompt, int $maxTokens = 2048, ?Company $company = null): string;

    /**
     * @param  list<array<string, mixed>>  $tools
     */
    public function toolCall(string $system, string $user, array $tools, ?Company $company = null): string;

    /**
     * @return array{suggestion: string, model: string, input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public function analyzeDocument(string $system, string $user, string $base64, string $mimeType, int $maxTokens = 4096, ?Company $company = null): array;
}
