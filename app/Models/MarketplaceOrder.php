<?php

namespace App\Models;

use App\Enums\MarketplaceOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceOrder extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => MarketplaceOrderStatus::class,
            'total_minor' => 'integer',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function formattedTotal(): string
    {
        return flowdesk_format_minor((int) $this->total_minor, $this->currency).' '.$this->currency;
    }

    public function paymentReferenceLabel(): string
    {
        return (string) ($this->payment_reference ?: $this->order_number);
    }

    public function isPaid(): bool
    {
        return $this->status === MarketplaceOrderStatus::Paid;
    }
}
