<?php

namespace App\AI\Nova\Support;

use App\AI\Nova\Agent\NovaContext;
use App\Models\Company;
use App\Models\User;
use RuntimeException;

class NovaSecurity
{
    public function assertActor(User $user, Company $company): void
    {
        if ($user->company_id === null || (string) $user->company_id !== (string) $company->id) {
            throw new RuntimeException('Nova request failed tenant isolation check.');
        }

        if (! $user->hasAnyRole(['company_admin', 'team_member'])) {
            throw new RuntimeException('Nova is only available to workspace staff.');
        }
    }

    public function assertContext(NovaContext $context): void
    {
        $this->assertActor($context->user, $context->company);
    }

    /**
     * Strip / reject untrusted entity IDs from AI output before tool execution.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function sanitizeArguments(array $arguments): array
    {
        $clean = [];
        foreach ($arguments as $key => $value) {
            if (is_string($value)) {
                $clean[$key] = trim($value);
            } elseif (is_int($value) || is_float($value) || is_bool($value) || is_null($value)) {
                $clean[$key] = $value;
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitizeArguments($value);
            }
        }

        return $clean;
    }
}
