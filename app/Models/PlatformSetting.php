<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'theme_defaults' => 'array',
            'theme_library' => 'array',
            'invoice_pdf_library' => 'array',
            'project_settings' => 'array',
            'payment_credentials' => 'array',
            'openai_api_key_encrypted' => 'encrypted',
            'anthropic_api_key_encrypted' => 'encrypted',
            'google_api_key_encrypted' => 'encrypted',
        ];
    }
}
