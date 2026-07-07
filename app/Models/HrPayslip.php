<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPayslip extends Model
{
    use HasUlids, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'breakdown' => 'array',
            'gross_minor' => 'integer',
            'deductions_minor' => 'integer',
            'net_minor' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(HrPayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }
}
