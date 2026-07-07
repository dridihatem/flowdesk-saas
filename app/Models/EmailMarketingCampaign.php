<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMarketingCampaign extends Model
{
    use HasUlids, TenantScope;

    protected $table = 'email_marketing_campaigns';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function audience(): BelongsTo
    {
        return $this->belongsTo(EmailMarketingAudience::class, 'audience_id');
    }

    /**
     * @return BelongsToMany<EmailMarketingAudienceContact, $this, EmailMarketingAudienceContact>
     */
    public function selectedAudienceContacts(): BelongsToMany
    {
        return $this->belongsToMany(
            EmailMarketingAudienceContact::class,
            'email_marketing_campaign_recipients',
            'email_marketing_campaign_id',
            'email_marketing_audience_contact_id',
        );
    }

    /**
     * @return HasMany<EmailMarketingRecipientDelivery, $this>
     */
    public function recipientDeliveries(): HasMany
    {
        return $this->hasMany(EmailMarketingRecipientDelivery::class, 'email_marketing_campaign_id');
    }
}
