<?php

namespace App\Enums;

enum ProviderPartnershipStatus: string
{
    case Active = 'active';
    case PendingProvider = 'pending_provider';
    case PendingCompany = 'pending_company';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Partnership active'),
            self::PendingProvider => __('Awaiting provider signature'),
            self::PendingCompany => __('Awaiting company signature'),
        };
    }
}
