<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Models\Concerns\TenantScope;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory, HasUlids, SoftDeletes, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'amount' => 'integer',
            'subtotal_amount' => 'integer',
            'vat_amount' => 'integer',
            'fiscal_stamp_amount' => 'integer',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(Negotiation::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class)->orderBy('id');
    }

    public function allocatedVatMinorForLineHt(int $lineTotalHtMinor): int
    {
        if ($lineTotalHtMinor <= 0 || $this->subtotal_amount <= 0 || $this->vat_amount <= 0) {
            return 0;
        }

        return (int) round(($lineTotalHtMinor * $this->vat_amount) / $this->subtotal_amount);
    }

    public function lineTotalTtcDisplayMinor(int $lineTotalHtMinor): int
    {
        return $lineTotalHtMinor + $this->allocatedVatMinorForLineHt($lineTotalHtMinor);
    }
}
