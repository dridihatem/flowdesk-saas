<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMarketingRecipientDelivery extends Model
{
    use HasUlids;

    public const KIND_MASS = 'mass';

    public const KIND_SAMPLE = 'sample';

    protected $table = 'email_marketing_recipient_deliveries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'first_opened_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailMarketingCampaign::class, 'email_marketing_campaign_id');
    }

    public function audienceContact(): BelongsTo
    {
        return $this->belongsTo(EmailMarketingAudienceContact::class, 'email_marketing_audience_contact_id');
    }
}
