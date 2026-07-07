<?php

namespace App\Enums;

enum ClientFeedbackKind: string
{
    case Team = 'team';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::Team => __('client_feedback_kind_team'),
            self::Provider => __('client_feedback_kind_provider'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
