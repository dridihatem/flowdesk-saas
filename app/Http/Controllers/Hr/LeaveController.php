<?php

namespace App\Http\Controllers\Hr;

use App\Enums\HrLeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Hr\Concerns\AuthorizesHrWorkspace;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveType;
use App\Services\HrSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveController extends Controller
{
    use AuthorizesHrWorkspace;

    public function index(Request $request, HrSetupService $setup): View
    {
        $company = $this->authorizeHr($request);
        $setup->ensureDefaults($company);

        $requests = HrLeaveRequest::query()
            ->where('company_id', $company->id)
            ->with(['employee:id,full_name,employee_number', 'leaveType:id,name,color'])
            ->latest()
            ->paginate(20);

        $leaveTypes = HrLeaveType::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employees = HrEmployee::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_number']);

        return view('hr.leave.index', compact('requests', 'leaveTypes', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->authorizeHr($request);

        $data = $request->validate([
            'employee_id' => ['required', 'ulid', Rule::exists('hr_employees', 'id')->where('company_id', $company->id)],
            'leave_type_id' => ['required', 'ulid', Rule::exists('hr_leave_types', 'id')->where('company_id', $company->id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $starts = Carbon::parse($data['starts_on']);
        $ends = Carbon::parse($data['ends_on']);
        $days = max(1, $starts->diffInDays($ends) + 1);

        HrLeaveRequest::query()->create([
            'company_id' => $company->id,
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'days_count' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => HrLeaveRequestStatus::Pending,
        ]);

        return redirect()->route('hr.leave.index')->with('status', __('hr_leave_request_saved'));
    }

    public function approve(Request $request, HrLeaveRequest $leaveRequest): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertLeaveRequest($company, $leaveRequest);

        $leaveRequest->update([
            'status' => HrLeaveRequestStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('hr_leave_request_approved'));
    }

    public function reject(Request $request, HrLeaveRequest $leaveRequest): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertLeaveRequest($company, $leaveRequest);

        $leaveRequest->update([
            'status' => HrLeaveRequestStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('hr_leave_request_rejected'));
    }
}
