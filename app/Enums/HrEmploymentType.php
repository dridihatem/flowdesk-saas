<?php

namespace App\Enums;

enum HrEmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Intern = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => __('hr_employment_full_time'),
            self::PartTime => __('hr_employment_part_time'),
            self::Contract => __('hr_employment_contract'),
            self::Intern => __('hr_employment_intern'),
        };
    }
}
