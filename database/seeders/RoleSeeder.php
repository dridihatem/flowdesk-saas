<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['company_admin', 'team_member', 'business_provider'] as $name) {
            Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }
    }
}
