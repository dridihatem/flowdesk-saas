<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hr\Concerns\AuthorizesHrWorkspace;
use App\Models\HrDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use AuthorizesHrWorkspace;

    public function index(Request $request): View
    {
        $company = $this->authorizeHr($request);

        $departments = HrDepartment::query()
            ->where('company_id', $company->id)
            ->with(['manager:id,name', 'parent:id,name'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return view('hr.departments.index', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->authorizeHr($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'ulid', Rule::exists('hr_departments', 'id')->where('company_id', $company->id)],
            'manager_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('company_id', $company->id)],
        ]);

        HrDepartment::query()->create($data);

        return redirect()->route('hr.departments.index')->with('status', __('hr_department_saved'));
    }

    public function update(Request $request, HrDepartment $department): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertDepartment($company, $department);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'ulid', Rule::exists('hr_departments', 'id')->where('company_id', $company->id)],
            'manager_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('company_id', $company->id)],
        ]);

        if (isset($data['parent_id']) && $data['parent_id'] === $department->id) {
            $data['parent_id'] = null;
        }

        $department->update($data);

        return redirect()->route('hr.departments.index')->with('status', __('hr_department_saved'));
    }

    public function destroy(Request $request, HrDepartment $department): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertDepartment($company, $department);

        if ($department->employees()->exists()) {
            return redirect()->route('hr.departments.index')->withErrors([
                'department' => __('hr_department_has_employees'),
            ]);
        }

        $department->delete();

        return redirect()->route('hr.departments.index')->with('status', __('hr_department_deleted'));
    }
}
