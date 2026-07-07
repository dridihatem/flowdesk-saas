<?php

namespace App\Models;

use App\Models\Concerns\ParsesModuleManifest;
use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalledModule extends Model
{
    use HasUlids, ParsesModuleManifest, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'settings' => 'array',
            'is_enabled' => 'boolean',
            'installed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function navIcon(): string
    {
        $icon = $this->manifest['nav']['icon'] ?? null;

        return is_string($icon) && $icon !== '' ? $icon : 'modules';
    }

    public function localizedName(?string $locale = null): string
    {
        return $this->localizedManifestField('name', $locale) ?? $this->name;
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        return $this->localizedManifestField('description', $locale) ?? $this->description;
    }

    private function localizedManifestField(string $field, ?string $locale): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $locales = $this->manifest['locales'] ?? null;
        if (! is_array($locales)) {
            return null;
        }

        $entry = $locales[$locale] ?? $locales['en'] ?? null;
        if (! is_array($entry)) {
            return null;
        }

        $value = $entry[$field] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
