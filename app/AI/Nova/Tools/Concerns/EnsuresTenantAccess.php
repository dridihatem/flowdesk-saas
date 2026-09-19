<?php

namespace App\AI\Nova\Tools\Concerns;

use App\AI\Nova\Agent\NovaContext;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

trait EnsuresTenantAccess
{
    protected function assertPermission(NovaContext $context, string $permission): void
    {
        if (! $context->can($permission)) {
            throw new RuntimeException("Missing permission: {$permission}");
        }
    }

    protected function assertSameCompany(NovaContext $context, Model $model): void
    {
        $companyId = $model->getAttribute('company_id');
        if ($companyId === null || (string) $companyId !== (string) $context->companyId) {
            throw new RuntimeException('Resource not found in this workspace.');
        }
    }

    /**
     * Never trust AI-supplied IDs without tenant verification.
     */
    protected function findInCompany(string $modelClass, string $id, NovaContext $context): Model
    {
        /** @var Model $model */
        $model = $modelClass::query()
            ->withoutGlobalScopes()
            ->whereKey($id)
            ->where('company_id', $context->companyId)
            ->first();

        if ($model === null) {
            throw new RuntimeException('Resource not found in this workspace.');
        }

        return $model;
    }
}
