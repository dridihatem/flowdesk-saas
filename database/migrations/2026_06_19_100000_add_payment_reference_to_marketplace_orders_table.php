<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->string('payment_reference', 32)->nullable()->after('order_number');
            $table->index('payment_reference');
        });

        DB::table('marketplace_orders')
            ->whereNull('payment_reference')
            ->update([
                'payment_reference' => DB::raw('order_number'),
            ]);
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropIndex(['payment_reference']);
            $table->dropColumn('payment_reference');
        });
    }
};
