<?php

namespace App\Models;

use App\Enums\NegotiationStatus;
use App\Models\Concerns\TenantScope;
use Database\Factories\NegotiationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Negotiation extends Model
{
    /** @use HasFactory<NegotiationFactory> */
    use HasFactory, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => NegotiationStatus::class,
            'amount' => 'integer',
            'commission_amount_minor' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
