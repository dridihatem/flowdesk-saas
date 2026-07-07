<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3)->default('USD');
            $table->string('quote_currency', 3);
            // 1 base_currency = rate quote_currency
            $table->decimal('rate', 18, 8);
            $table->timestamp('as_of')->nullable();
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency']);
            $table->index(['quote_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
