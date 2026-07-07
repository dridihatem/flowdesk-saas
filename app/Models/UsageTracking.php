<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Database\Factories\UsageTrackingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageTracking extends Model
{
    /** @use HasFactory<UsageTrackingFactory> */
    use HasFactory, TenantScope;

    protected $table = 'usage_tracking';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
