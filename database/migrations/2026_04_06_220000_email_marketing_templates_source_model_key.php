<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_marketing_templates', function (Blueprint $table) {
            $table->string('source_model_key', 64)->nullable()->after('category');
            $table->unique(['company_id', 'source_model_key']);
        });
    }

    public function down(): void
    {
        Schema::table('email_marketing_templates', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'source_model_key']);
            $table->dropColumn('source_model_key');
        });
    }
};
