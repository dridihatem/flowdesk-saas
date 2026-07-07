<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_cod_collections')) {
            return;
        }

        Schema::create('module_cod_collections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('courier_name');
            $table->date('collection_date');
            $table->unsignedInteger('expected_qar')->default(0);
            $table->unsignedInteger('received_qar')->default(0);
            $table->string('status', 24)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_cod_collections');
    }
};
