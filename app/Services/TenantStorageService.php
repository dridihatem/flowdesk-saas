<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Storage;

class TenantStorageService
{
    public function bootstrap(Company $company): void
    {
        $disk = Storage::disk('tenant');

        foreach (['', 'private', 'public'] as $suffix) {
            $path = $suffix === '' ? $company->id : $company->id.'/'.$suffix;
            $disk->makeDirectory($path);
        }
    }
}
