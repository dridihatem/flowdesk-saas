<?php

namespace App\Models;

use App\Enums\HrPayrollRunStatus;
use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrPayrollRun extends Model
{
    use HasUlids, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => HrPayrollRunStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'pay_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(HrPayslip::class, 'payroll_run_id');
    }

    public function totalGrossMinor(): int
    {
        return (int) $this->payslips()->sum('gross_minor');
    }

    public function totalNetMinor(): int
    {
        return (int) $this->payslips()->sum('net_minor');
    }
}
