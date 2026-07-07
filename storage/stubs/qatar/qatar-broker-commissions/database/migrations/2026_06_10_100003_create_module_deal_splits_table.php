<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_deal_splits')) {
            return;
        }

        Schema::create('module_deal_splits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUlid('provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('title');
            $table->unsignedInteger('deal_amount_qar')->default(0);
            $table->unsignedTinyInteger('commission_pct')->default(2);
            $table->string('status', 24)->default('pipeline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_deal_splits');
    }
};
