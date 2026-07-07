<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'disabled_at' => 'datetime',
            'plan_locked' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function syncPlanFromSubscriptions(): void
    {
        if ($this->plan_locked) {
            return;
        }

        $sub = $this->subscriptions()
            ->withoutGlobalScopes()
            ->whereIn('status', ['active', 'trialing'])
            ->latest('id')
            ->first();
        $planId = $sub?->plan_id;
        if ((string) $this->plan_id !== (string) ($planId ?? '')) {
            $this->forceFill(['plan_id' => $planId])->saveQuietly();
        }
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(Form::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function usageTracking(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    public function marketingSupportTickets(): HasMany
    {
        return $this->hasMany(MarketingSupport::class);
    }

    public function negotiations(): HasMany
    {
        return $this->hasMany(Negotiation::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Persist a new company API token (for widgets / server-to-server). Returns the plain token once.
     */
    public function regenerateApiToken(): string
    {
        $plain = 'fd_live_'.Str::random(40);
        $this->api_token_hash = hash('sha256', $plain);
        $this->api_token_hint = substr($plain, -8);
        // Kept encrypted so it can be re-displayed and injected into embed snippets.
        $this->api_token_encrypted = Crypt::encryptString($plain);
        $this->save();

        return $plain;
    }

    /**
     * Plain embed token, or null for legacy tokens generated before encryption was stored.
     */
    public function apiTokenPlain(): ?string
    {
        if (empty($this->api_token_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_token_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function apiTokenMatches(string $plain): bool
    {
        if ($this->api_token_hash === null) {
            return false;
        }

        return hash_equals($this->api_token_hash, hash('sha256', $plain));
    }

    /**
     * Public signup URL for business providers (central app host).
     */
    public function providerRecruitmentUrl(): ?string
    {
        if (! $this->provider_recruitment_enabled || $this->provider_recruitment_slug === null || $this->provider_recruitment_slug === '') {
            return null;
        }

        return flowdesk_public_site_url('partner/'.$this->provider_recruitment_slug);
    }
}
