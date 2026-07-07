<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('payment_credentials')->nullable()->after('theme_defaults');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->string('job_title')->nullable()->after('website');
            $table->text('description')->nullable()->after('job_title');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('payment_credentials');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'website', 'job_title', 'description']);
        });
    }
};
