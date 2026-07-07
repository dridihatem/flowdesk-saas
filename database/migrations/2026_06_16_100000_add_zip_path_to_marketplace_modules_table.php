<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_modules', function (Blueprint $table) {
            $table->string('zip_path')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_modules', function (Blueprint $table) {
            $table->dropColumn('zip_path');
        });
    }
};
