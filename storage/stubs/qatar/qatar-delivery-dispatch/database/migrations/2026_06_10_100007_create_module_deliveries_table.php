<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_deliveries')) {
            Schema::create('module_deliveries', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
                $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('reference');
                $table->string('zone')->nullable();
                $table->string('courier_name')->nullable();
                $table->string('status', 24)->default('assigned');
                $table->unsignedInteger('cod_qar')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('module_deliveries');
    }
};
