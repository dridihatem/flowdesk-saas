<?php

namespace App\Enums;

enum HrPayrollRunStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('hr_payroll_draft'),
            self::Finalized => __('hr_payroll_finalized'),
            self::Paid => __('hr_payroll_paid'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            self::Finalized => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200',
            self::Paid => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
        };
    }
}
