<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMarketingSequence extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'email_marketing_sequences';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
