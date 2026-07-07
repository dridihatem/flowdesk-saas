<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Plan;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\User;
use App\Services\TenantStorageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['subdomain' => 'demo'],
            [
                'name' => 'Flowqil Demo',
                'slug' => 'demo',
                'default_locale' => 'en',
                'default_currency' => 'TND',
                'country' => 'TN',
            ],
        );

        $company->update([
            'name' => 'Flowqil Demo',
        ]);

        CompanySetting::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'branding' => [
                    'logo_url' => null,
                ],
                'theme' => [
                    'mode' => 'light',
                    'layout_type' => 'sidebar',
                    'primary_color' => '#4f46e5',
                    'secondary_color' => '#64748b',
                    'font_family' => 'Figtree',
                    'dark_mode' => false,
                ],
                'dashboard' => [],
            ],
        );

        $pro = Plan::query()->where('slug', 'pro')->firstOrFail();

        Subscription::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan_id' => $pro->id,
                'status' => 'active',
                'trial_ends_at' => null,
                'current_period_end' => now()->addMonth(),
            ],
        );

        app(TenantStorageService::class)->bootstrap($company);

        $password = Hash::make('password');

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@demo.local'],
            [
                'name' => 'Demo Admin',
                'password' => $password,
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ],
        );
        if (! $admin->hasRole('company_admin')) {
            $admin->assignRole('company_admin');
        }

        $member = User::query()->firstOrCreate(
            ['email' => 'team@demo.local'],
            [
                'name' => 'Demo Team Member',
                'password' => $password,
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ],
        );
        if (! $member->hasRole('team_member')) {
            $member->assignRole('team_member');
        }

        $providerUser = User::query()->firstOrCreate(
            ['email' => 'provider@demo.local'],
            [
                'name' => 'Demo Provider',
                'password' => $password,
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ],
        );
        if (! $providerUser->hasRole('business_provider')) {
            $providerUser->assignRole('business_provider');
        }

        Provider::query()->firstOrCreate(
            ['user_id' => $providerUser->id],
            [
                'company_id' => $company->id,
                'name' => 'Demo Provider',
                'email' => $providerUser->email,
                'commission_rate' => 0.1,
            ],
        );

        $legacy = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => $password,
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ],
        );
        if (! $legacy->hasRole('company_admin')) {
            $legacy->assignRole('company_admin');
        }

        $this->call(ClientEmailSampleSeeder::class);
    }
}
