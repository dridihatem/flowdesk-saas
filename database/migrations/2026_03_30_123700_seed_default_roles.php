<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['company_admin', 'team_member', 'business_provider'] as $name) {
            Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }
    }

    public function down(): void
    {
        Role::query()->whereIn('name', ['company_admin', 'team_member', 'business_provider'])->delete();
    }
};
