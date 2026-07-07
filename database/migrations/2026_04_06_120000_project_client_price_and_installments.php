<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->timestamp('client_price_confirmed_at')->nullable()->after('negotiated_price');
        });

        Schema::create('project_installments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('due_date');
            $table->unsignedBigInteger('amount_minor');
            $table->string('payment_method', 32);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_installments');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('client_price_confirmed_at');
        });
    }
};
