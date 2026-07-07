<?php

namespace App\Enums;

enum HrLeaveRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('hr_leave_pending'),
            self::Approved => __('hr_leave_approved'),
            self::Rejected => __('hr_leave_rejected'),
            self::Cancelled => __('hr_leave_cancelled'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
            self::Approved => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
            self::Rejected => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200',
            self::Cancelled => 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}
