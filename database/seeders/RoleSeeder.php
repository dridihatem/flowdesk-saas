<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            'platform_admin',
            'company_admin',
            'team_member',
            'business_provider',
            'client',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']),
        ]);

        $permissions = collect([
            // Platform (central app)
            'platform.manage_permissions',
            'platform.view_companies',
            'platform.manage_companies',
            'platform.manage_plans',
            'platform.view_revenue',

            // Company workspace
            'workspace.view_dashboard',
            'workspace.view_analytics',
            'workspace.manage_projects',
            'workspace.manage_inquiries',
            'workspace.manage_invoices',
            'workspace.manage_providers',
            'workspace.manage_clients',
            'workspace.manage_team',
            'workspace.manage_settings',
            'workspace.manage_subscription',
            'workspace.access_vault',
            'workspace.manage_hr',

            // Client portal
            'portal.view_projects',
            'portal.view_payments',
            'portal.view_proposals',
            'portal.view_invoices',
            'portal.request_quote',
            'portal.suggest_client_account',

            // Provider portal
            'provider.view_dashboard',
            'provider.manage_projects',
            'provider.view_commissions',
            'provider.view_payments',
        ])->map(function (string $name): Permission {
            return Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        });

        $roles['platform_admin']->syncPermissions($permissions->filter(fn (Permission $p): bool => str_starts_with($p->name, 'platform.'))->all());

        $roles['company_admin']->syncPermissions([
            'workspace.view_dashboard',
            'workspace.view_analytics',
            'workspace.manage_projects',
            'workspace.manage_inquiries',
            'workspace.manage_invoices',
            'workspace.manage_providers',
            'workspace.manage_clients',
            'workspace.manage_team',
            'workspace.manage_settings',
            'workspace.manage_subscription',
            'workspace.access_vault',
            'workspace.manage_hr',
        ]);

        $roles['team_member']->syncPermissions([
            'workspace.view_dashboard',
            'workspace.manage_projects',
            'workspace.manage_inquiries',
            'workspace.manage_invoices',
            'workspace.manage_clients',
        ]);

        $roles['client']->syncPermissions([
            'portal.view_projects',
            'portal.view_payments',
            'portal.view_proposals',
            'portal.view_invoices',
            'portal.request_quote',
            'portal.suggest_client_account',
        ]);

        $roles['business_provider']->syncPermissions([
            'provider.view_dashboard',
            'provider.manage_projects',
            'provider.view_commissions',
            'provider.view_payments',
        ]);
    }
}
