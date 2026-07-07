<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScope
{
    public static function bootTenantScope(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $company = app()->bound('currentCompany') ? app()->make('currentCompany') : null;

            if ($company instanceof Company) {
                $builder->where($builder->getModel()->getTable().'.company_id', $company->id);
            }
        });

        static::creating(function (Model $model): void {
            if (filled($model->company_id)) {
                return;
            }

            $company = app()->bound('currentCompany') ? app()->make('currentCompany') : null;

            if ($company instanceof Company) {
                $model->company_id = $company->id;

                return;
            }

            $user = auth()->user();
            if ($user !== null && filled($user->company_id)) {
                $model->company_id = $user->company_id;
            }
        });
    }
}
