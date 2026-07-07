<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_calendar_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('kind', 32)->default('meeting');
            $table->string('google_calendar_event_id', 300)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_calendar_events');
    }
};
