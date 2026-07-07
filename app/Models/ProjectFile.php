<?php

namespace App\Models;

use App\Enums\ProjectFileCategory;
use App\Models\Concerns\TenantScope;
use App\Support\PublicDiskUrl;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'project_files';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_vault' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectFile $file): void {
            if ($file->path) {
                Storage::disk($file->disk)->delete($file->path);
            }
            if ($file->thumb_path) {
                Storage::disk($file->disk)->delete($file->thumb_path);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProjectFileShare::class);
    }

    public function isVault(): bool
    {
        return (bool) $this->is_vault;
    }

    public function url(): string
    {
        if ($this->disk === 'public') {
            return PublicDiskUrl::forPath($this->path);
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function thumbUrl(): ?string
    {
        if (! $this->thumb_path) {
            return null;
        }

        if ($this->disk === 'public') {
            return PublicDiskUrl::forPath($this->thumb_path);
        }

        return Storage::disk($this->disk)->url($this->thumb_path);
    }

    public function isImage(): bool
    {
        $m = strtolower((string) $this->mime);

        return str_starts_with($m, 'image/') && ! str_contains($m, 'svg');
    }

    public function categoryEnum(): ProjectFileCategory
    {
        return ProjectFileCategory::tryFrom((string) $this->category) ?? ProjectFileCategory::Other;
    }
}
