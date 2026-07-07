<?php

namespace App\Enums;

enum MarketplaceModuleBillingPeriod: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case OneTime = 'one_time';

    public function label(): string
    {
        return __('marketplace_module_billing.'.$this->value);
    }
}
