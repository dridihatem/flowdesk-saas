<?php

namespace App\Services;

use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrLeaveType;
use App\Models\User;

class HrSetupService
{
    /**
     * @return list<array{code: string, name: string, days_per_year: int, is_paid: bool, color: string}>
     */
    public function defaultLeaveTypes(): array
    {
        return [
            ['code' => 'annual', 'name' => __('hr_leave_type_annual'), 'days_per_year' => 22, 'is_paid' => true, 'color' => 'emerald'],
            ['code' => 'sick', 'name' => __('hr_leave_type_sick'), 'days_per_year' => 10, 'is_paid' => true, 'color' => 'rose'],
            ['code' => 'unpaid', 'name' => __('hr_leave_type_unpaid'), 'days_per_year' => 0, 'is_paid' => false, 'color' => 'slate'],
        ];
    }

    public function ensureDefaults(Company $company): void
    {
        if (HrLeaveType::query()->where('company_id', $company->id)->exists()) {
            return;
        }

        foreach ($this->defaultLeaveTypes() as $type) {
            HrLeaveType::query()->create([
                'company_id' => $company->id,
                ...$type,
            ]);
        }
    }

    public function nextEmployeeNumber(Company $company): string
    {
        $count = HrEmployee::query()
            ->where('company_id', $company->id)
            ->count();

        return 'EMP-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function syncWorkspaceStaffEmployees(Company $company): int
    {
        $staffUsers = User::query()
            ->where('company_id', $company->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['company_admin', 'team_member']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $created = 0;

        foreach ($staffUsers as $user) {
            $employee = HrEmployee::query()
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->first();

            if ($employee) {
                $employee->fill([
                    'email' => $employee->email ?: $user->email,
                ])->save();

                continue;
            }

            HrEmployee::query()->create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'employee_number' => $this->nextEmployeeNumber($company),
                'full_name' => $user->name,
                'email' => $user->email,
                'status' => 'active',
                'employment_type' => 'full_time',
                'pay_frequency' => 'monthly',
                'salary_currency' => flowdesk_normalize_currency_code($company->default_currency ?? 'USD'),
                'base_salary_minor' => 0,
            ]);

            $created++;
        }

        return $created;
    }
}
