<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('payment_method');
        });

        DB::update('UPDATE payments SET paid_at = created_at WHERE paid_at IS NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['company_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'paid_at']);
            $table->dropColumn('paid_at');
        });
    }
};
