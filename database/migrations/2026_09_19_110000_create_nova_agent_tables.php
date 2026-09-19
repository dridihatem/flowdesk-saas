<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nova_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id', 'updated_at']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('nova_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('conversation_id')->constrained('nova_conversations')->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('nova_runs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('conversation_id')->constrained('nova_conversations')->cascadeOnDelete();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('input');
            $table->string('status', 32)->default('pending');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('nova_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('run_id')->constrained('nova_runs')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32)->default('running');
            $table->string('message');
            $table->string('tool_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'created_at']);
        });

        Schema::create('nova_action_audits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('conversation_id')->nullable()->constrained('nova_conversations')->nullOnDelete();
            $table->foreignUlid('run_id')->nullable()->constrained('nova_runs')->nullOnDelete();
            $table->string('tool_name');
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->string('risk_level', 16)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['run_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nova_action_audits');
        Schema::dropIfExists('nova_activities');
        Schema::dropIfExists('nova_runs');
        Schema::dropIfExists('nova_messages');
        Schema::dropIfExists('nova_conversations');
    }
};
