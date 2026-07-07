<?php

namespace App\Enums;

enum HrPayFrequency: string
{
    case Monthly = 'monthly';
    case Biweekly = 'biweekly';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => __('hr_pay_monthly'),
            self::Biweekly => __('hr_pay_biweekly'),
            self::Weekly => __('hr_pay_weekly'),
        };
    }
}
