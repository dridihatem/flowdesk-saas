<?php

use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrPayrollRun;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PlanSeeder::class);
});

function ensureHrCompanySubscription($company): void
{
    app(\App\Services\SubscriptionBootstrapService::class)->ensureDefaultSubscription($company);
}

test('company admin can access hr dashboard on pro plan', function () {
    $user = User::factory()->create();
    ensureHrCompanySubscription($user->company);

    $this->actingAs($user)
        ->get(route('hr.index'))
        ->assertOk()
        ->assertSee(__('hr_dashboard_title'));
});

test('team member cannot access hr module', function () {
    $user = User::factory()->create();
    ensureHrCompanySubscription($user->company);
    $user->syncRoles(['team_member']);

    $this->actingAs($user)
        ->get(route('hr.index'))
        ->assertForbidden();
});

test('admin can sync internal team users into hr employees', function () {
    $admin = User::factory()->create();
    ensureHrCompanySubscription($admin->company);

    $teamMember = User::factory()->create(['company_id' => $admin->company_id]);
    $teamMember->syncRoles(['team_member']);

    $businessProvider = User::factory()->create(['company_id' => $admin->company_id]);
    $businessProvider->syncRoles(['business_provider']);

    $this->actingAs($admin)
        ->post(route('hr.employees.sync-team'))
        ->assertRedirect(route('hr.employees.index'));

    expect(HrEmployee::query()->where('user_id', $admin->id)->exists())->toBeTrue()
        ->and(HrEmployee::query()->where('user_id', $teamMember->id)->exists())->toBeTrue()
        ->and(HrEmployee::query()->where('user_id', $businessProvider->id)->exists())->toBeFalse();
});

test('admin can create employee department and payroll run', function () {
    $user = User::factory()->create();
    ensureHrCompanySubscription($user->company);

    $this->actingAs($user)
        ->post(route('hr.departments.store'), ['name' => 'Engineering'])
        ->assertRedirect(route('hr.departments.index'));

    $department = HrDepartment::query()->where('company_id', $user->company_id)->first();
    expect($department)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('hr.employees.store'), [
            'full_name' => 'Jane Doe',
            'employment_type' => 'full_time',
            'status' => 'active',
            'pay_frequency' => 'monthly',
            'department_id' => $department->id,
            'salary_amount' => '2500',
            'salary_currency' => 'USD',
        ])
        ->assertRedirect(route('hr.employees.index'));

    $employee = HrEmployee::query()->where('company_id', $user->company_id)->first();
    expect($employee?->full_name)->toBe('Jane Doe')
        ->and($employee?->base_salary_minor)->toBeGreaterThan(0);

    $this->actingAs($user)
        ->post(route('hr.payroll.store'), [
            'title' => 'July payroll',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'pay_date' => now()->endOfMonth()->toDateString(),
        ])
        ->assertRedirect();

    $run = HrPayrollRun::query()->where('company_id', $user->company_id)->first();
    expect($run)->not->toBeNull();

    $this->actingAs($user)
        ->post(route('hr.payroll.generate', $run))
        ->assertRedirect();

    expect($run->fresh()->payslips()->count())->toBe(1);
});
