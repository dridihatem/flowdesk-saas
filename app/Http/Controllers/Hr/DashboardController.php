<?php

namespace App\Http\Controllers\Hr;

use App\Enums\HrEmployeeStatus;
use App\Enums\HrLeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Hr\Concerns\AuthorizesHrWorkspace;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrPayrollRun;
use App\Services\HrSetupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AuthorizesHrWorkspace;

    public function __invoke(Request $request, HrSetupService $setup): View
    {
        $company = $this->authorizeHr($request);
        $setup->ensureDefaults($company);
        $setup->syncWorkspaceStaffEmployees($company);

        $employeesCount = HrEmployee::query()->where('company_id', $company->id)->count();
        $activeEmployees = HrEmployee::query()
            ->where('company_id', $company->id)
            ->where('status', HrEmployeeStatus::Active)
            ->count();
        $departmentsCount = HrDepartment::query()->where('company_id', $company->id)->count();
        $pendingLeave = HrLeaveRequest::query()
            ->where('company_id', $company->id)
            ->where('status', HrLeaveRequestStatus::Pending)
            ->count();
        $latestPayroll = HrPayrollRun::query()
            ->where('company_id', $company->id)
            ->latest()
            ->first();

        $recentEmployees = HrEmployee::query()
            ->where('company_id', $company->id)
            ->with('department:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('hr.dashboard', compact(
            'employeesCount',
            'activeEmployees',
            'departmentsCount',
            'pendingLeave',
            'latestPayroll',
            'recentEmployees',
        ));
    }
}
