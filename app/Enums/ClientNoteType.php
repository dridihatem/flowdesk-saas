<?php

namespace App\Enums;

enum ClientNoteType: string
{
    case Meeting = 'meeting';
    case Reminder = 'reminder';
    case Call = 'call';
    case Email = 'email';
    case General = 'general';
    case FollowUp = 'follow_up';

    public function label(): string
    {
        return match ($this) {
            self::Meeting => __('client_note_type_meeting'),
            self::Reminder => __('client_note_type_reminder'),
            self::Call => __('client_note_type_call'),
            self::Email => __('client_note_type_email'),
            self::General => __('client_note_type_general'),
            self::FollowUp => __('client_note_type_follow_up'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
