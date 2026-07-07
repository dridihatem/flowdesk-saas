<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_modules', function (Blueprint $table) {
            $table->json('target_countries')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_modules', function (Blueprint $table) {
            $table->dropColumn('target_countries');
        });
    }
};
