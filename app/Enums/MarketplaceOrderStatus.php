<?php

namespace App\Enums;

enum MarketplaceOrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('marketplace_order_status.pending'),
            self::Paid => __('marketplace_order_status.paid'),
            self::Cancelled => __('marketplace_order_status.cancelled'),
            self::Failed => __('marketplace_order_status.failed'),
        };
    }
}
