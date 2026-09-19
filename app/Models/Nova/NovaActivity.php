<?php

namespace App\Models\Nova;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaActivity extends Model
{
    use HasUlids;

    public const TYPES = [
        'thinking',
        'searching',
        'reading',
        'checking',
        'analyzing',
        'creating',
        'updating',
        'sending',
        'calculating',
        'planning',
        'waiting',
        'completed',
        'error',
    ];

    protected $table = 'nova_activities';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(NovaRun::class, 'run_id');
    }
}
