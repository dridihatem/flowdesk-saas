<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Database\Factories\MarketingSupportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingSupport extends Model
{
    /** @use HasFactory<MarketingSupportFactory> */
    use HasFactory, TenantScope;

    protected $table = 'marketing_support';

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
