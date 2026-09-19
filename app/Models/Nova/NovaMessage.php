<?php

namespace App\Models\Nova;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovaMessage extends Model
{
    use HasUlids;

    public const ROLE_SYSTEM = 'system';

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    public const ROLE_TOOL = 'tool';

    public const ROLES = [
        self::ROLE_SYSTEM,
        self::ROLE_USER,
        self::ROLE_ASSISTANT,
        self::ROLE_TOOL,
    ];

    protected $table = 'nova_messages';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(NovaConversation::class, 'conversation_id');
    }
}
