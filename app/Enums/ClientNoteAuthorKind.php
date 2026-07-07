<?php

namespace App\Enums;

enum ClientNoteAuthorKind: string
{
    case Team = 'team';
    case Provider = 'provider';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Team => __('client_note_author_team'),
            self::Provider => __('client_note_author_provider'),
            self::Company => __('client_note_author_company'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
