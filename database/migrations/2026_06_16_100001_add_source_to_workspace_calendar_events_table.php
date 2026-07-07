<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_calendar_events', function (Blueprint $table) {
            if (! Schema::hasColumn('workspace_calendar_events', 'source_type')) {
                $table->string('source_type', 64)->nullable()->after('kind');
                $table->string('source_id', 26)->nullable()->after('source_type');
                $table->index(['company_id', 'source_type', 'source_id'], 'wce_company_source_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspace_calendar_events', function (Blueprint $table) {
            if (Schema::hasColumn('workspace_calendar_events', 'source_type')) {
                $table->dropIndex('wce_company_source_idx');
                $table->dropColumn(['source_type', 'source_id']);
            }
        });
    }
};
