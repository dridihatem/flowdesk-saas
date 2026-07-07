<?php

namespace App\Enums;

enum TaskPriceMode: string
{
    /** Amount rolls into the project lump (shown on invoice as designation, 0 unit if listed). */
    case Bundled = 'bundled';
    /** Own line amount; adds to invoice when billable. */
    case Additive = 'additive';

    public function label(): string
    {
        return match ($this) {
            self::Bundled => __('Included in project price'),
            self::Additive => __('Own price (adds to invoice if billable)'),
        };
    }
}
