<?php

namespace App\Enums;

enum TaskScope: string
{
    case Core = 'core';
    case Extra = 'extra';

    public function label(): string
    {
        return match ($this) {
            self::Core => __('Included in project scope'),
            self::Extra => __('Additional / extra work'),
        };
    }
}
