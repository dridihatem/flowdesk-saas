<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('order_number', 32)->unique();
            $table->string('status', 16)->default('pending');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_company')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('company_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('total_minor');
            $table->string('currency', 3);
            $table->string('stripe_checkout_session_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('customer_email');
        });

        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('marketplace_order_id')->constrained('marketplace_orders')->cascadeOnDelete();
            $table->foreignUlid('marketplace_module_id')->nullable()->constrained('marketplace_modules')->nullOnDelete();
            $table->string('module_slug');
            $table->string('module_name');
            $table->unsignedBigInteger('price_minor');
            $table->string('currency', 3);
            $table->string('billing_period', 16);
            $table->timestamps();

            $table->index('marketplace_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
        Schema::dropIfExists('marketplace_orders');
    }
};
