<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrLeaveType extends Model
{
    use HasUlids, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
            'days_per_year' => 'integer',
        ];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class, 'leave_type_id');
    }

    public function localizedName(): string
    {
        return match ($this->code) {
            'annual' => __('hr_leave_type_annual'),
            'sick' => __('hr_leave_type_sick'),
            'unpaid' => __('hr_leave_type_unpaid'),
            default => $this->name,
        };
    }
}
