<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_period_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('period_months');
            $table->unsignedBigInteger('price_minor');
            $table->timestamps();

            $table->unique(['plan_id', 'period_months']);
        });

        $plans = DB::table('plans')->select('id', 'price_monthly', 'currency')->get();
        foreach ($plans as $plan) {
            $monthlyMajor = (int) $plan->price_monthly;
            $perMonthMinor = (int) ($monthlyMajor * 100);
            foreach ([3, 6, 12] as $m) {
                DB::table('plan_period_prices')->insert([
                    'plan_id' => $plan->id,
                    'period_months' => $m,
                    'price_minor' => $perMonthMinor * $m,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_period_prices');
    }
};
