<?php

namespace App\Enums;

enum HrEmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('hr_status_active'),
            self::OnLeave => __('hr_status_on_leave'),
            self::Terminated => __('hr_status_terminated'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
            self::OnLeave => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
            self::Terminated => 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}
