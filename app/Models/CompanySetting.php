<?php

namespace App\Models;

use App\Models\Concerns\TenantScope;
use Database\Factories\CompanySettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    /** @use HasFactory<CompanySettingFactory> */
    use HasFactory, TenantScope;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'branding' => 'array',
            'smtp' => 'array',
            'theme' => 'array',
            'dashboard' => 'array',
            'navigation' => 'array',
            'ui_presets' => 'array',
            'payment_credentials' => 'array',
            'document_templates' => 'array',
            'security' => 'array',
            'provider_commission_client_tiers' => 'array',
            'billing' => 'array',
            'marketing' => 'array',
            'integration_channels' => 'array',
            'ai_agent' => 'array',
            'workspace_openai_api_key_encrypted' => 'encrypted',
            'workspace_anthropic_api_key_encrypted' => 'encrypted',
            'workspace_google_api_key_encrypted' => 'encrypted',
            'zoom_client_id_encrypted' => 'encrypted',
            'zoom_client_secret_encrypted' => 'encrypted',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
