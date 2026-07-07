<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('project_settings')->nullable()->after('theme_library');
            $table->text('openai_api_key_encrypted')->nullable()->after('project_settings');
            $table->string('openai_model', 64)->nullable()->after('openai_api_key_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['project_settings', 'openai_api_key_encrypted', 'openai_model']);
        });
    }
};
