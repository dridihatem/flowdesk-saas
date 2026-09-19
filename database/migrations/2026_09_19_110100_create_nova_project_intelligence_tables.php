<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'order']);
            $table->index(['company_id', 'project_id']);
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUlid('file_id')->nullable()->constrained('project_files')->nullOnDelete();
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->string('status', 32)->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['project_id', 'status']);
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->foreignUlid('phase_id')->nullable()->after('project_id')->constrained('project_phases')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->after('phase_id')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('order')->nullable()->after('sort_order');
            $table->string('priority', 16)->nullable()->after('status');
            $table->unsignedInteger('estimated_hours')->nullable()->after('priority');
            $table->foreignUlid('source_document_id')->nullable()->after('estimated_hours')->constrained('project_documents')->nullOnDelete();
            $table->unsignedInteger('source_page')->nullable()->after('source_document_id');
            $table->string('source_section')->nullable()->after('source_page');
            $table->text('source_text')->nullable()->after('source_section');
            $table->boolean('ai_generated')->default(false)->after('source_text');
            $table->decimal('ai_confidence', 5, 4)->nullable()->after('ai_generated');
        });

        Schema::create('project_task_dependencies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignUlid('depends_on_task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_task_id']);
            $table->index(['company_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_dependencies');

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_document_id');
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropConstrainedForeignId('phase_id');
            $table->dropColumn([
                'order',
                'priority',
                'estimated_hours',
                'source_page',
                'source_section',
                'source_text',
                'ai_generated',
                'ai_confidence',
            ]);
        });

        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_phases');
    }
};
