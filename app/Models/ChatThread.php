<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model
{
    use HasUlids, TenantScope;

    public const TYPE_CLIENT = 'client';

    public const TYPE_PROVIDER = 'provider';

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_thread_participants')->withTimestamps();
    }

    public function resolveSubjectName(): string
    {
        return $this->resolveDisplayNameFor(auth()->user());
    }

    public function resolveDisplayNameFor(?User $viewer): string
    {
        if ($viewer && ($viewer->hasRole('client') || $viewer->hasRole('business_provider'))) {
            return $this->company?->name ?? Company::query()->where('id', $this->company_id)->value('name') ?? __('Company');
        }

        if ($this->type === self::TYPE_CLIENT) {
            return Client::query()->withoutGlobalScopes()
                ->where('company_id', $this->company_id)
                ->where('id', $this->subject_id)
                ->value('name') ?? __('Client');
        }

        return Provider::query()->withoutGlobalScopes()
            ->where('company_id', $this->company_id)
            ->where('id', $this->subject_id)
            ->value('name') ?? __('Provider');
    }
}
