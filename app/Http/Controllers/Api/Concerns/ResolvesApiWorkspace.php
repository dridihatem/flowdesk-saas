<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Company;

trait ResolvesApiWorkspace
{
    protected function apiCompany(): Company
    {
        $company = app()->bound('currentCompany') ? app('currentCompany') : null;
        abort_unless($company instanceof Company, 404, __('Tenant not found.'));

        return $company;
    }
}
