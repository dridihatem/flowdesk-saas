<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->text('anthropic_api_key_encrypted')->nullable()->after('openai_model');
            $table->string('claude_model', 64)->nullable()->after('anthropic_api_key_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['anthropic_api_key_encrypted', 'claude_model']);
        });
    }
};
