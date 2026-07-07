<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('scope', 16)->default('core')->after('description');
            $table->string('price_mode', 16)->default('bundled')->after('scope');
            $table->timestamp('tracking_started_at')->nullable()->after('amount_cents');
            $table->unsignedInteger('tracking_accumulated_seconds')->default(0)->after('tracking_started_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignUlid('project_id')->nullable()->after('proposal_id')->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
        });

        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn(['scope', 'price_mode', 'tracking_started_at', 'tracking_accumulated_seconds']);
        });
    }
};
