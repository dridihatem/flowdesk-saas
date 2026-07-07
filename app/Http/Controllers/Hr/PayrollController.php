<?php

namespace App\Http\Controllers\Hr;

use App\Enums\HrPayrollRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Hr\Concerns\AuthorizesHrWorkspace;
use App\Models\HrPayrollRun;
use App\Services\HrPayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PayrollController extends Controller
{
    use AuthorizesHrWorkspace;

    public function index(Request $request): View
    {
        $company = $this->authorizeHr($request);

        $runs = HrPayrollRun::query()
            ->where('company_id', $company->id)
            ->withCount('payslips')
            ->latest()
            ->paginate(15);

        return view('hr.payroll.index', compact('runs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $currency = flowdesk_normalize_currency_code($company->default_currency ?? 'USD');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'pay_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $run = HrPayrollRun::query()->create([
            ...$data,
            'company_id' => $company->id,
            'currency' => $currency,
            'status' => HrPayrollRunStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('hr.payroll.show', $run)->with('status', __('hr_payroll_created'));
    }

    public function show(Request $request, HrPayrollRun $payrollRun): View
    {
        $company = $this->authorizeHr($request);
        $this->assertPayrollRun($company, $payrollRun);

        $payrollRun->load([
            'payslips' => fn ($q) => $q->with('employee:id,full_name,employee_number,job_title')->orderBy('created_at'),
            'creator:id,name',
        ]);

        return view('hr.payroll.show', ['run' => $payrollRun, 'currency' => $payrollRun->currency]);
    }

    public function generate(Request $request, HrPayrollRun $payrollRun, HrPayrollService $payroll): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertPayrollRun($company, $payrollRun);

        $count = $payroll->generatePayslips($payrollRun, $company);

        return back()->with('status', __('hr_payroll_payslips_generated', ['count' => $count]));
    }

    public function finalize(Request $request, HrPayrollRun $payrollRun, HrPayrollService $payroll): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertPayrollRun($company, $payrollRun);

        $payroll->finalize($payrollRun);

        return back()->with('status', __('hr_payroll_finalized_notice'));
    }

    public function markPaid(Request $request, HrPayrollRun $payrollRun, HrPayrollService $payroll): RedirectResponse
    {
        $company = $this->authorizeHr($request);
        $this->assertPayrollRun($company, $payrollRun);

        $payroll->markPaid($payrollRun);

        return back()->with('status', __('hr_payroll_marked_paid'));
    }

    public function suggestPeriod(Request $request): array
    {
        $company = $this->authorizeHr($request);
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return [
            'title' => __('hr_payroll_default_title', ['month' => $start->translatedFormat('F Y')]),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'pay_date' => $end->copy()->addDays(5)->toDateString(),
            'currency' => flowdesk_normalize_currency_code($company->default_currency ?? 'USD'),
        ];
    }
}
