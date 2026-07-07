<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMarketingAudienceContact extends Model
{
    use HasUlids;

    protected $table = 'email_marketing_audience_contacts';

    protected $guarded = ['id'];

    public function audience(): BelongsTo
    {
        return $this->belongsTo(EmailMarketingAudience::class, 'audience_id');
    }
}
