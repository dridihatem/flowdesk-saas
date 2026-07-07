<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_orders')) {
            Schema::create('module_orders', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
                $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('order_number', 32);
                $table->string('channel', 32)->default('website');
                $table->string('status', 24)->default('new');
                $table->string('payment_method', 24)->default('cod');
                $table->unsignedInteger('total_qar')->default(0);
                $table->timestamps();
                $table->unique(['company_id', 'order_number']);
            });
        }

        if (! Schema::hasTable('module_order_lines')) {
            Schema::create('module_order_lines', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('order_id')->constrained('module_orders')->cascadeOnDelete();
                $table->string('sku')->nullable();
                $table->string('label');
                $table->unsignedSmallInteger('qty')->default(1);
                $table->unsignedInteger('unit_price_qar')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('module_order_lines');
        Schema::dropIfExists('module_orders');
    }
};
