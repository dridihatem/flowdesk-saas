<?php

namespace App\AI\Nova\Agent;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Public façade for the Nova agent runtime.
 */
class NovaAgent
{
    public function __construct(private NovaOrchestrator $orchestrator) {}

    /**
     * @param  array{page?: string|null, entity?: array{type: string, id: string}|null}|null  $pageContext
     * @param  array<string, mixed>|null  $confirmation
     */
    public function run(
        User $user,
        Company $company,
        string $input,
        ?string $conversationId = null,
        ?array $pageContext = null,
        ?array $confirmation = null,
        ?Request $request = null,
    ): NovaResponse {
        return $this->orchestrator->handle(
            $user,
            $company,
            $input,
            $conversationId,
            $pageContext,
            $confirmation,
            $request,
        );
    }
}
