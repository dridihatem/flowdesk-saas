<?php

namespace App\Http\Controllers\Hr\Concerns;

use App\Models\Company;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrPayrollRun;
use Illuminate\Http\Request;

trait AuthorizesHrWorkspace
{
    protected function authorizeHr(Request $request): Company
    {
        abort_if(! $request->user()->can('workspace.manage_hr'), 403);
        $company = $request->user()->company;
        abort_if(! $company instanceof Company, 403);

        return $company;
    }

    protected function assertEmployee(Company $company, HrEmployee $employee): void
    {
        abort_if((string) $employee->company_id !== (string) $company->id, 404);
    }

    protected function assertDepartment(Company $company, HrDepartment $department): void
    {
        abort_if((string) $department->company_id !== (string) $company->id, 404);
    }

    protected function assertLeaveRequest(Company $company, HrLeaveRequest $leaveRequest): void
    {
        abort_if((string) $leaveRequest->company_id !== (string) $company->id, 404);
    }

    protected function assertPayrollRun(Company $company, HrPayrollRun $payrollRun): void
    {
        abort_if((string) $payrollRun->company_id !== (string) $company->id, 404);
    }
}
