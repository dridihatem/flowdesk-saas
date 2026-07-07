<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installed_modules', function (Blueprint $table) {
            if (! Schema::hasColumn('installed_modules', 'settings')) {
                $table->json('settings')->nullable()->after('manifest');
            }
        });
    }

    public function down(): void
    {
        Schema::table('installed_modules', function (Blueprint $table) {
            if (Schema::hasColumn('installed_modules', 'settings')) {
                $table->dropColumn('settings');
            }
        });
    }
};
