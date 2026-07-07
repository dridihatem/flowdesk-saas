<?php

namespace App\Enums;

enum ProjectFileCategory: string
{
    case Document = 'document';
    case Spec = 'spec';
    case Screenshot = 'screenshot';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Document => __('General document'),
            self::Spec => __('Statement of work / specs'),
            self::Screenshot => __('Screenshot'),
            self::Other => __('Other'),
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
