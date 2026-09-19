<?php

namespace App\AI\Nova\Tools;

use App\AI\Nova\Agent\NovaContext;
use Illuminate\Support\Collection;

class ToolRegistry
{
    /** @var array<string, NovaTool> */
    private array $tools = [];

    /** @var array<string, string|null> permission required, null = any authenticated staff */
    private array $permissions = [];

    public function register(NovaTool $tool, ?string $permission = null): void
    {
        $this->tools[$tool->name()] = $tool;
        $this->permissions[$tool->name()] = $permission;
    }

    public function get(string $name): ?NovaTool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return array<string, NovaTool>
     */
    public function forContext(NovaContext $context): array
    {
        $out = [];
        foreach ($this->tools as $name => $tool) {
            $permission = $this->permissions[$name] ?? null;
            if ($permission === null || $context->can($permission)) {
                $out[$name] = $tool;
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function schemasFor(NovaContext $context): array
    {
        return Collection::make($this->forContext($context))
            ->map(fn (NovaTool $tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'schema' => $tool->schema(),
            ])
            ->values()
            ->all();
    }
}
