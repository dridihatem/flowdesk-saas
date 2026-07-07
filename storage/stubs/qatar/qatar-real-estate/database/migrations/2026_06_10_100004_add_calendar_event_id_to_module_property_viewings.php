<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_property_viewings')) {
            return;
        }

        Schema::table('module_property_viewings', function (Blueprint $table) {
            if (! Schema::hasColumn('module_property_viewings', 'calendar_event_id')) {
                $table->foreignUlid('calendar_event_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('workspace_calendar_events')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_property_viewings')) {
            return;
        }

        Schema::table('module_property_viewings', function (Blueprint $table) {
            if (Schema::hasColumn('module_property_viewings', 'calendar_event_id')) {
                $table->dropConstrainedForeignId('calendar_event_id');
            }
        });
    }
};
