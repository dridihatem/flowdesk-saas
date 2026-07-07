<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_calendar_events', function (Blueprint $table) {
            if (! Schema::hasColumn('workspace_calendar_events', 'client_id')) {
                $table->foreignUlid('client_id')->nullable()->after('created_by')->constrained('clients')->nullOnDelete();
                $table->index(['company_id', 'client_id', 'starts_on']);
            }
            if (! Schema::hasColumn('workspace_calendar_events', 'start_time')) {
                $table->time('start_time')->nullable()->after('starts_on');
            }
            if (! Schema::hasColumn('workspace_calendar_events', 'meeting_summary')) {
                $table->text('meeting_summary')->nullable()->after('description');
            }
            if (! Schema::hasColumn('workspace_calendar_events', 'invite_sent_at')) {
                $table->timestamp('invite_sent_at')->nullable()->after('google_calendar_event_id');
            }
        });

        if (! Schema::hasTable('client_feedbacks')) {
            Schema::create('client_feedbacks', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
                $table->foreignUlid('client_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('body');
                $table->timestamps();

                $table->index(['client_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_feedbacks');

        Schema::table('workspace_calendar_events', function (Blueprint $table) {
            foreach (['client_id', 'start_time', 'meeting_summary', 'invite_sent_at'] as $column) {
                if (Schema::hasColumn('workspace_calendar_events', $column)) {
                    if ($column === 'client_id') {
                        $table->dropForeign(['client_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
