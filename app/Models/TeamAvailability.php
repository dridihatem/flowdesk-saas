<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamAvailability extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'team_availability';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'available_hours' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
