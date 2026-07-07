<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMarketplaceModuleDismissal extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'company_purchased_module_dismissals';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceModule(): BelongsTo
    {
        return $this->belongsTo(MarketplaceModule::class);
    }
}
