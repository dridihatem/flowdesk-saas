<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('plan_id');
            $table->timestamp('disabled_at')->nullable()->after('is_enabled');
            $table->string('disabled_reason', 255)->nullable()->after('disabled_at');
            $table->boolean('plan_locked')->default(false)->after('disabled_reason');

            $table->index(['is_enabled']);
            $table->index(['plan_locked']);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['is_enabled']);
            $table->dropIndex(['plan_locked']);
            $table->dropColumn(['is_enabled', 'disabled_at', 'disabled_reason', 'plan_locked']);
        });
    }
};
