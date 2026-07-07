<?php

namespace App\Enums;

enum CalendarMeetingLinkType: string
{
    case None = 'none';
    case Url = 'url';
    case Zoom = 'zoom';
    case GoogleMeet = 'google_meet';

    public function label(): string
    {
        return match ($this) {
            self::None => __('calendar_meeting_none'),
            self::Url => __('calendar_meeting_custom_url'),
            self::Zoom => __('calendar_meeting_zoom'),
            self::GoogleMeet => __('calendar_meeting_google_meet'),
        };
    }
}
