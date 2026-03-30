<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Persist a new company API token (for widgets / server-to-server). Returns the plain token once.
     */
    public function regenerateApiToken(): string
    {
        $plain = 'fd_live_'.Str::random(40);
        $this->api_token_hash = hash('sha256', $plain);
        $this->api_token_hint = substr($plain, -8);
        $this->save();

        return $plain;
    }

    public function apiTokenMatches(string $plain): bool
    {
        if ($this->api_token_hash === null) {
            return false;
        }

        return hash_equals($this->api_token_hash, hash('sha256', $plain));
    }
}
