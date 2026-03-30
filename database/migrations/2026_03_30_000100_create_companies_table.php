<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('slug')->unique();
            $table->string('default_locale', 10)->default('en');
            $table->string('default_currency', 3)->default('USD');
            $table->timestamps();
            $table->softDeletes();

            $table->index('subdomain');
            $table->index('slug');
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
