<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderItem extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(MarketplaceModule::class, 'marketplace_module_id');
    }

    public function formattedPrice(): string
    {
        return flowdesk_format_minor((int) $this->price_minor, $this->currency).' '.$this->currency;
    }
}
