<?php

namespace App\Models\Nova;

use App\Models\Company;
use App\Models\Concerns\TenantScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NovaConversation extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'nova_conversations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public function messages(): HasMany
    {
        return $this->hasMany(NovaMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(NovaRun::class, 'conversation_id');
    }
}
