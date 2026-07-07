<?php

namespace App\Http\Controllers\Hr;

use App\Enums\HrEmployeeStatus;
use App\Enums\HrEmploymentType;
use App\Enums\HrPayFrequency;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Hr\Concerns\AuthorizesHrWorkspace;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\User;
use App\Services\HrSetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use AuthorizesHrWorkspace;

    public function index(Request $request, HrSetupService $setup): View
    {
        $company = $this->authorizeHr($request);
        $importedCount = $setup->syncWorkspaceStaffEmployees($company);
        $q = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        $query = HrEmployee::query()
            ->where('company_id', $company->id)
            ->with(['department:id,name', 'user:id,name,email'])
            ->orderBy('full_name');

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('full_name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('employee_number', 'like', '%'.$q.'%')
                    ->orWhere('job_title', 'like', '%'.$q.'%');
            });
        }

        if ($status !== '' && in_array($status, array_column(HrEmployeeStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        $employees = $query->paginate(20)->withQueryString();

        return view('hr.employees.index', compact('employees', 'q', 'status', 'importedCount'));
    }

    public function create(Request $request, HrSetupService $setup): View
    {
        $company = $this->authorizeHr($request);

        return view('hr.employees.create', $this->formPayload($company, $setup));
    }

    public function store(Request $request, HrSetupService $setup): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $data = $this->validatedEmployee($request, $company);

        if (empty($data['employee_number'])) {
            $data['employee_number'] = $setup->nextEmployeeNumber($company);
        }

        HrEmployee::query()->create($data);

        return redirect()->route('hr.employees.index')->with('status', __('hr_employee_saved'));
    }

    public function show(Request $request, HrEmployee $employee): View
    {
        $company = $this->authorizeHr($request);
        $this->assertEmployee($company, $employee);

        $employee->load([
            'department',
            'user:id,name,email',
            'leaveRequests' => fn ($q) => $q->with('leaveType:id,name')->latest()->limit(10),
            'payslips' => fn ($q) => $q->with('payrollRun:id,title,pay_date')->latest()->limit(10),
        ]);

        return view('hr.employees.show', compact('employee'));
    }

    public function edit(Request $request, HrEmployee $employee, HrSetupService $setup): View
    {
        $company = $this->authorizeHr($request);
        $this->assertEmployee($company, $employee);

        return view('hr.employees.edit', [
            'employee' => $employee,
            ...$this->formPayload($company, $setup),
        ]);
    }

    public function update(Request $request, HrEmployee $employee): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertEmployee($company, $employee);

        $data = $this->validatedEmployee($request, $company, $employee);
        $employee->update($data);

        return redirect()->route('hr.employees.show', $employee)->with('status', __('hr_employee_saved'));
    }

    public function syncTeam(Request $request, HrSetupService $setup): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $count = $setup->syncWorkspaceStaffEmployees($company);

        return redirect()
            ->route('hr.employees.index')
            ->with('status', __('hr_team_synced_to_employees', ['count' => $count]));
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload($company, HrSetupService $setup): array
    {
        return [
            'departments' => HrDepartment::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'staffUsers' => User::query()->where('company_id', $company->id)->workspaceStaff()->orderBy('name')->get(['id', 'name', 'email']),
            'employmentTypes' => HrEmploymentType::cases(),
            'statuses' => HrEmployeeStatus::cases(),
            'payFrequencies' => HrPayFrequency::cases(),
            'defaultCurrency' => flowdesk_normalize_currency_code($company->default_currency ?? 'USD'),
            'suggestedEmployeeNumber' => $setup->nextEmployeeNumber($company),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedEmployee(Request $request, $company, ?HrEmployee $employee = null): array
    {
        $userRule = Rule::exists('users', 'id')->where('company_id', $company->id);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', $userRule],
            'department_id' => ['nullable', 'ulid', Rule::exists('hr_departments', 'id')->where('company_id', $company->id)],
            'employee_number' => ['nullable', 'string', 'max:32'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', Rule::enum(HrEmploymentType::class)],
            'status' => ['required', Rule::enum(HrEmployeeStatus::class)],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'salary_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'salary_currency' => ['nullable', 'string', 'size:3', flowdesk_currency_rule()],
            'pay_frequency' => ['required', Rule::enum(HrPayFrequency::class)],
            'bank_iban' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $currency = flowdesk_normalize_currency_code(
            $data['salary_currency'] ?? $company->default_currency ?? 'USD'
        );
        $amountRaw = $request->input('salary_amount');
        $baseSalaryMinor = ($amountRaw !== null && $amountRaw !== '')
            ? flowdesk_decimal_to_minor((string) $amountRaw, $currency)
            : 0;

        return [
            'company_id' => $company->id,
            'user_id' => $data['user_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'employee_number' => $data['employee_number'] ?? $employee?->employee_number,
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'employment_type' => $data['employment_type'],
            'status' => $data['status'],
            'hire_date' => $data['hire_date'] ?? null,
            'termination_date' => $data['termination_date'] ?? null,
            'base_salary_minor' => $baseSalaryMinor,
            'salary_currency' => $currency,
            'pay_frequency' => $data['pay_frequency'],
            'bank_iban' => $data['bank_iban'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
