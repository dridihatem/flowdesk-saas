<?php

namespace App\Enums;

enum ClientSource: string
{
    case Manual = 'manual';
    case WebsiteForm = 'website_form';
    case Referral = 'referral';
    case SocialMedia = 'social_media';
    case Advertising = 'advertising';
    case BusinessProvider = 'business_provider';
    case Inquiry = 'inquiry';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('client_source_manual'),
            self::WebsiteForm => __('client_source_website_form'),
            self::Referral => __('client_source_referral'),
            self::SocialMedia => __('client_source_social_media'),
            self::Advertising => __('client_source_advertising'),
            self::BusinessProvider => __('client_source_business_provider'),
            self::Inquiry => __('client_source_inquiry'),
            self::Other => __('client_source_other'),
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
