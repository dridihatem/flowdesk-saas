<?php

namespace App\Models;

use App\Enums\HrEmployeeStatus;
use App\Enums\HrEmploymentType;
use App\Enums\HrPayFrequency;
use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrEmployee extends Model
{
    use HasUlids, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'employment_type' => HrEmploymentType::class,
            'status' => HrEmployeeStatus::class,
            'pay_frequency' => HrPayFrequency::class,
            'hire_date' => 'date',
            'termination_date' => 'date',
            'address' => 'array',
            'base_salary_minor' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'department_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class, 'employee_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(HrPayslip::class, 'employee_id');
    }

    public function salaryCurrency(string $fallback = 'USD'): string
    {
        return flowdesk_normalize_currency_code($this->salary_currency ?? $fallback);
    }

    public function formattedSalary(): string
    {
        $currency = $this->salaryCurrency();

        return flowdesk_format_minor((int) $this->base_salary_minor, $currency).' '.$currency;
    }

    public function isPayrollEligible(): bool
    {
        return $this->status === HrEmployeeStatus::Active;
    }
}
