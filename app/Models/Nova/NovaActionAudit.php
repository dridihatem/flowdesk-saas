<?php

namespace App\Models\Nova;

use App\Models\Company;
use App\Models\Concerns\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaActionAudit extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'nova_action_audits';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'result' => 'array',
            'confirmed' => 'boolean',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(NovaConversation::class, 'conversation_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(NovaRun::class, 'run_id');
    }
}
