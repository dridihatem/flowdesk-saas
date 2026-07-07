<?php

namespace App\Models;

use App\Enums\TaskPriceMode;
use App\Enums\TaskScope;
use App\Enums\TaskStatus;
use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ProjectTask extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'project_tasks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'scope' => TaskScope::class,
            'price_mode' => TaskPriceMode::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'billable' => 'boolean',
            'amount_cents' => 'integer',
            'tracking_started_at' => 'datetime',
            'tracking_accumulated_seconds' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectTaskFile::class, 'project_task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectTaskComment::class, 'project_task_id')->orderBy('created_at');
    }

    public function displayCurrency(?string $fallback = null): string
    {
        $raw = $this->currency;
        if ($raw === null || trim((string) $raw) === '') {
            return flowdesk_normalize_currency_code($fallback);
        }

        return flowdesk_normalize_currency_code($raw);
    }

    public function formattedAmount(?string $currencyFallback = null): ?string
    {
        if ($this->amount_cents === null) {
            return null;
        }

        $code = $this->displayCurrency($currencyFallback);

        return flowdesk_format_minor((int) $this->amount_cents, $code).' '.$code;
    }

    public function isOverdue(): bool
    {
        if ($this->ends_on === null || $this->status === TaskStatus::Done) {
            return false;
        }

        return Carbon::today()->isAfter($this->ends_on);
    }

    /** Total tracked work seconds (accumulated + current running segment). */
    public function elapsedTrackingSeconds(): int
    {
        $acc = (int) ($this->tracking_accumulated_seconds ?? 0);
        if ($this->tracking_started_at === null) {
            return $acc;
        }

        return $acc + max(0, (int) $this->tracking_started_at->diffInSeconds(now()));
    }
}
