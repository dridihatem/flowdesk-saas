<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_products')) {
            return;
        }

        Schema::create('module_products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64);
            $table->string('name');
            $table->string('category')->nullable();
            $table->unsignedInteger('price_qar')->default(0);
            $table->integer('stock_qty')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_products');
    }
};
