<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('company_marketplace_module_dismissals');

        if (Schema::hasTable('company_purchased_module_dismissals')) {
            return;
        }

        Schema::create('company_purchased_module_dismissals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('module_slug');
            $table->foreignUlid('marketplace_module_id')->nullable();
            $table->timestamp('dismissed_at');
            $table->timestamps();

            $table->unique(['company_id', 'module_slug'], 'co_purch_mod_dismiss_slug_uq');
            $table->foreign('marketplace_module_id', 'co_purch_mod_dismiss_mod_fk')
                ->references('id')
                ->on('marketplace_modules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_purchased_module_dismissals');
        Schema::dropIfExists('company_marketplace_module_dismissals');
    }
};
