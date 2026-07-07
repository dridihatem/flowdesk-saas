<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('flowdesk.platform_admin_email', env('FLOWDESK_PLATFORM_ADMIN_EMAIL', 'platform@demo.local'));
        $password = (string) env('FLOWDESK_PLATFORM_ADMIN_PASSWORD', 'password');

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make($password),
                'company_id' => null,
                'locale' => 'en',
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill([
            'company_id' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles(['platform_admin']);
    }
}
