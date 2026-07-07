<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('api_token_hint');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->json('addons')->nullable()->after('currency');
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->json('security')->nullable()->after('document_templates');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('security');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('addons');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }
};
