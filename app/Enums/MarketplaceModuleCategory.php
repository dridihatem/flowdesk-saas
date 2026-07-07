<?php

namespace App\Enums;

enum MarketplaceModuleCategory: string
{
    case Finance = 'finance';
    case Hr = 'hr';
    case RealEstate = 'real_estate';
    case Ecommerce = 'ecommerce';
    case Delivery = 'delivery';
    case Pos = 'pos';
    case Saas = 'saas';
    case Services = 'services';
    case General = 'general';

    public function label(): string
    {
        return __('marketplace_module_category.'.$this->value);
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Finance => 10,
            self::Hr => 20,
            self::RealEstate => 30,
            self::Ecommerce => 40,
            self::Delivery => 50,
            self::Pos => 60,
            self::Saas => 70,
            self::Services => 80,
            self::General => 90,
        };
    }

    /**
     * @return list<self>
     */
    public static function orderedCases(): array
    {
        return collect(self::cases())
            ->sortBy(fn (self $case) => $case->sortOrder())
            ->values()
            ->all();
    }
}
