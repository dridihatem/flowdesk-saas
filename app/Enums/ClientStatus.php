<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Potential = 'potential';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::Potential => __('client_status_potential'),
            self::Active => __('client_status_active'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Potential => 'bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
            self::Active => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
