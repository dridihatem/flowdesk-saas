<?php

namespace App\Services;

use App\Enums\HrPayrollRunStatus;
use App\Models\Company;
use App\Models\HrEmployee;
use App\Models\HrPayrollRun;
use App\Models\HrPayslip;
use Illuminate\Support\Facades\DB;

class HrPayrollService
{
    public function generatePayslips(HrPayrollRun $run, Company $company): int
    {
        abort_if($run->status !== HrPayrollRunStatus::Draft, 422, __('hr_payroll_not_draft'));

        $currency = flowdesk_normalize_currency_code($run->currency ?? $company->default_currency ?? 'USD');

        $employees = HrEmployee::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();

        return DB::transaction(function () use ($run, $employees, $currency): int {
            $run->payslips()->delete();
            $created = 0;

            foreach ($employees as $employee) {
                if (! $employee->isPayrollEligible()) {
                    continue;
                }

                $gross = (int) $employee->base_salary_minor;
                $deductions = 0;
                $net = max(0, $gross - $deductions);

                HrPayslip::query()->create([
                    'company_id' => $run->company_id,
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'gross_minor' => $gross,
                    'deductions_minor' => $deductions,
                    'net_minor' => $net,
                    'currency' => $employee->salaryCurrency($currency),
                    'breakdown' => [
                        'base_salary' => $gross,
                        'deductions' => $deductions,
                    ],
                    'status' => 'draft',
                ]);
                $created++;
            }

            return $created;
        });
    }

    public function finalize(HrPayrollRun $run): void
    {
        abort_if($run->status !== HrPayrollRunStatus::Draft, 422, __('hr_payroll_not_draft'));
        abort_if($run->payslips()->count() === 0, 422, __('hr_payroll_no_payslips'));

        $run->update([
            'status' => HrPayrollRunStatus::Finalized,
            'finalized_at' => now(),
        ]);
    }

    public function markPaid(HrPayrollRun $run): void
    {
        abort_if($run->status !== HrPayrollRunStatus::Finalized, 422, __('hr_payroll_not_finalized'));

        DB::transaction(function () use ($run): void {
            $run->payslips()->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $run->update(['status' => HrPayrollRunStatus::Paid]);
        });
    }
}
