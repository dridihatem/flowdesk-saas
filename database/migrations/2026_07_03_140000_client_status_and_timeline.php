<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'status')) {
                $table->string('status', 32)->default('active')->after('source');
                $table->index(['company_id', 'status']);
            }
        });

        Schema::table('client_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('client_feedbacks', 'kind')) {
                $table->string('kind', 32)->default('team')->after('user_id');
            }
            if (! Schema::hasColumn('client_feedbacks', 'provider_id')) {
                $table->foreignUlid('provider_id')->nullable()->after('kind')->constrained('providers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('client_feedbacks', 'provider_id')) {
                $table->dropForeign(['provider_id']);
                $table->dropColumn('provider_id');
            }
            if (Schema::hasColumn('client_feedbacks', 'kind')) {
                $table->dropColumn('kind');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'status')) {
                $table->dropIndex(['company_id', 'status']);
                $table->dropColumn('status');
            }
        });
    }
};
