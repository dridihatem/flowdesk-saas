<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->json('ai_agent')->nullable()->after('integration_channels');
            $table->text('workspace_openai_api_key_encrypted')->nullable()->after('ai_agent');
            $table->text('workspace_anthropic_api_key_encrypted')->nullable()->after('workspace_openai_api_key_encrypted');
            $table->text('workspace_google_api_key_encrypted')->nullable()->after('workspace_anthropic_api_key_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_agent',
                'workspace_openai_api_key_encrypted',
                'workspace_anthropic_api_key_encrypted',
                'workspace_google_api_key_encrypted',
            ]);
        });
    }
};
