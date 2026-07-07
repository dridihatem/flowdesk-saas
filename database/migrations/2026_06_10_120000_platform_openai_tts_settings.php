<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('openai_tts_model', 32)->nullable()->after('openai_model');
            $table->string('openai_tts_voice', 32)->nullable()->after('openai_tts_model');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['openai_tts_model', 'openai_tts_voice']);
        });
    }
};
