<?php

namespace App\Models;

use App\Enums\ClientNoteAuthorKind;
use App\Enums\ClientNoteType;
use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNote extends Model
{
    use HasUlids, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'author_kind' => ClientNoteAuthorKind::class,
            'note_type' => ClientNoteType::class,
            'noted_on' => 'date',
            'visible_to_client' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function authorLabel(?string $companyName = null): string
    {
        return match ($this->author_kind) {
            ClientNoteAuthorKind::Provider => $this->provider?->name ?? __('Provider'),
            ClientNoteAuthorKind::Company => $companyName ?? __('Company'),
            default => $this->author?->name ?? __('Team'),
        };
    }
}
