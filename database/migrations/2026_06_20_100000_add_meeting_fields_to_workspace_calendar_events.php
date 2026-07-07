<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_calendar_events', function (Blueprint $table) {
            $table->string('meeting_link_type', 32)->default('none')->after('kind');
            $table->string('meeting_url', 2048)->nullable()->after('meeting_link_type');
            $table->string('zoom_meeting_id', 64)->nullable()->after('meeting_url');
            $table->string('google_meet_url', 2048)->nullable()->after('zoom_meeting_id');
        });
    }

    public function down(): void
    {
        Schema::table('workspace_calendar_events', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_link_type',
                'meeting_url',
                'zoom_meeting_id',
                'google_meet_url',
            ]);
        });
    }
};
