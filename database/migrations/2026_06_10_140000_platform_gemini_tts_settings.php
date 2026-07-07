<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->string('tts_provider', 16)->nullable()->after('openai_tts_voice');
            $table->string('gemini_tts_model', 64)->nullable()->after('tts_provider');
            $table->string('gemini_tts_voice', 32)->nullable()->after('gemini_tts_model');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn(['tts_provider', 'gemini_tts_model', 'gemini_tts_voice']);
        });
    }
};
