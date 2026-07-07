<?php

namespace App\Models;

use App\Enums\ProviderPartnershipStatus;
use App\Models\Concerns\TenantScope;
use Database\Factories\ProviderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    /** @use HasFactory<ProviderFactory> */
    use HasFactory, HasUlids, SoftDeletes, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:4',
            'commission_tiers' => 'array',
            'partnership_status' => ProviderPartnershipStatus::class,
            'partnership_provider_signed_at' => 'datetime',
            'partnership_company_signed_at' => 'datetime',
        ];
    }

    public function partnershipCompanySigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partnership_company_signer_user_id');
    }

    public function needsProviderPartnershipSignature(): bool
    {
        return $this->partnership_status === ProviderPartnershipStatus::PendingProvider;
    }

    public function needsCompanyPartnershipSignature(): bool
    {
        return $this->partnership_status === ProviderPartnershipStatus::PendingCompany;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPartnershipActive(): bool
    {
        return $this->partnership_status === ProviderPartnershipStatus::Active;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function remittanceRequests(): HasMany
    {
        return $this->hasMany(ProviderRemittanceRequest::class);
    }
}
