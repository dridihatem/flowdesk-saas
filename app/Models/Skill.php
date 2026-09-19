<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    use HasUlids;

    protected $table = 'skills';

    protected $guarded = ['id'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function memberSkills(): HasMany
    {
        return $this->hasMany(TeamMemberSkill::class);
    }

    /**
     * Platform catalog skills (company_id null) plus tenant-specific skills.
     */
    public function scopeForCompany(Builder $query, string $companyId): Builder
    {
        return $query->where(function (Builder $q) use ($companyId): void {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        });
    }
}
