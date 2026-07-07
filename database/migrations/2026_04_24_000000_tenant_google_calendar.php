<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->text('google_calendar_refresh_token_encrypted')->nullable();
            $table->string('google_calendar_connected_email')->nullable();
            $table->timestamp('google_calendar_connected_at')->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('google_calendar_event_id', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'google_calendar_refresh_token_encrypted',
                'google_calendar_connected_email',
                'google_calendar_connected_at',
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('google_calendar_event_id');
        });
    }
};
