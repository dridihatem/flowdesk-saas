<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Str;

class CompanyNamingService
{
    public function uniqueSubdomain(string $companyName): string
    {
        return $this->uniqueSegment($companyName, 'subdomain');
    }

    public function uniqueSlug(string $companyName): string
    {
        return $this->uniqueSegment($companyName, 'slug');
    }

    private function uniqueSegment(string $companyName, string $column): string
    {
        $base = Str::slug($companyName);
        $base = trim(Str::limit($base, 48, ''), '-');

        if ($base === '') {
            $base = 'company';
        }

        $candidate = $base;
        $i = 0;

        while (Company::query()->where($column, $candidate)->exists()) {
            $i++;
            $suffix = '-'.$i;
            $candidate = Str::limit($base, 48 - strlen($suffix), '').$suffix;
        }

        return $candidate;
    }
}
