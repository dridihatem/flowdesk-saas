<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $company = Company::query()->firstOrCreate(
            ['subdomain' => 'demo'],
            [
                'name' => 'Demo Company',
                'slug' => 'demo',
                'default_locale' => 'en',
                'default_currency' => 'USD',
            ],
        );

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('company_admin')) {
            $user->assignRole('company_admin');
        }

        app(\App\Services\TenantStorageService::class)->bootstrap($company);
    }
}
