<?php

namespace App\Enums;

enum ProjectSource: string
{
    case Internal = 'internal';
    case FormWebsite = 'form_website';
    case BusinessProvider = 'business_provider';
    case Inquiry = 'inquiry';

    public function label(): string
    {
        return match ($this) {
            self::Internal => __('Manual / internal'),
            self::FormWebsite => __('Lead form (website)'),
            self::BusinessProvider => __('Business provider'),
            self::Inquiry => __('Inquiry'),
        };
    }
}
