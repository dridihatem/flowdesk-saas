<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_currency', 3)->nullable()->after('locale');
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->boolean('billable')->default(true)->after('ends_on');
            $table->string('currency', 3)->nullable()->after('billable');
            $table->unsignedBigInteger('amount_cents')->nullable()->after('currency');
        });

        Schema::create('project_task_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'project_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_files');

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn(['billable', 'currency', 'amount_cents']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_currency');
        });
    }
};
