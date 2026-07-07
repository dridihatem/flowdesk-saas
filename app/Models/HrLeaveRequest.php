<?php

namespace App\Models;

use App\Enums\HrLeaveRequestStatus;
use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLeaveRequest extends Model
{
    use HasUlids, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => HrLeaveRequestStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'days_count' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'leave_type_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
