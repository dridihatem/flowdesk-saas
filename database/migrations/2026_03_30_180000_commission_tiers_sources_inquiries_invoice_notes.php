<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->json('commission_tiers')->nullable()->after('commission_rate');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('source', 32)->default('internal')->after('description');
            $table->foreignUlid('form_submission_id')->nullable()->after('source')->constrained('form_submissions')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->after('due_date');
            $table->text('customer_notes')->nullable()->after('internal_notes');
        });

        Schema::create('inquiries', function (Blueprint $ntable) {
            $ntable->ulid('id')->primary();
            $ntable->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $ntable->string('subject');
            $ntable->text('message')->nullable();
            $ntable->string('contact_name')->nullable();
            $ntable->string('contact_email')->nullable();
            $ntable->string('contact_phone')->nullable();
            $ntable->string('source')->nullable();
            $ntable->string('status')->default('new');
            $ntable->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $ntable->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $ntable->timestamps();

            $ntable->index('company_id');
            $ntable->index(['company_id', 'status']);
            $ntable->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['internal_notes', 'customer_notes']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['form_submission_id']);
            $table->dropColumn(['source', 'form_submission_id']);
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('commission_tiers');
        });
    }
};
