<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index('slug');
        });

        Schema::create('team_member_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(3);
            $table->unsignedSmallInteger('years_experience')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'skill_id']);
            $table->index(['company_id', 'user_id']);
        });

        Schema::create('project_task_skills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignUlid('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->unsignedTinyInteger('required_level')->default(3);
            $table->timestamps();

            $table->unique(['task_id', 'skill_id']);
            $table->index(['company_id', 'task_id']);
        });

        Schema::create('team_availability', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('available_hours', 5, 2)->default(8);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['company_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_availability');
        Schema::dropIfExists('project_task_skills');
        Schema::dropIfExists('team_member_skills');
        Schema::dropIfExists('skills');
    }
};
