<?php

namespace App\AI\Nova\Support;

use App\AI\Nova\Agent\NovaContext;
use App\Models\AuditLog;
use App\Models\Nova\NovaActionAudit;
use App\Models\Nova\NovaRun;

class NovaAuditLogger
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  mixed  $result
     */
    public function logTool(
        NovaContext $context,
        NovaRun $run,
        string $toolName,
        array $arguments,
        mixed $result,
        string $risk,
        bool $confirmed = false,
    ): void {
        NovaActionAudit::query()->create([
            'company_id' => $context->companyId,
            'user_id' => $context->userId,
            'conversation_id' => $context->conversationId,
            'run_id' => $run->id,
            'tool_name' => $toolName,
            'arguments' => $arguments,
            'result' => $this->normalizeResult($result),
            'confirmed' => $confirmed,
            'risk_level' => $risk,
        ]);

        AuditLog::query()->withoutGlobalScopes()->create([
            'company_id' => $context->companyId,
            'user_id' => $context->userId,
            'action' => 'nova.tool.'.$toolName,
            'auditable_type' => NovaRun::class,
            'auditable_id' => $run->id,
            'properties' => [
                'tool' => $toolName,
                'arguments' => $arguments,
                'confirmed' => $confirmed,
                'risk_level' => $risk,
                'conversation_id' => $context->conversationId,
            ],
            'ip_address' => request()?->ip(),
        ]);
    }

    private function normalizeResult(mixed $result): mixed
    {
        if (is_array($result) || is_null($result) || is_scalar($result)) {
            return $result;
        }

        return ['string' => (string) $result];
    }
}
