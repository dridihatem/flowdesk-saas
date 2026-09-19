<?php

namespace App\AI\Nova\Tools;

use App\AI\Nova\Agent\NovaContext;

interface NovaTool
{
    public function name(): string;

    public function description(): string;

    /**
     * JSON-schema-like argument definition for the LLM.
     *
     * @return array<string, mixed>
     */
    public function schema(): array;

    /**
     * Execute via Laravel services / Eloquent with tenant + permission checks.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function execute(array $arguments, NovaContext $context): mixed;
}
