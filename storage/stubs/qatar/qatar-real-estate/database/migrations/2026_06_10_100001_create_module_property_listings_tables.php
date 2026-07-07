<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_property_zones')) {
            Schema::create('module_property_zones', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('module_property_listings')) {
            Schema::create('module_property_listings', function (Blueprint $table) {
                $table->ulid('id')->primary();
                $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
                $table->foreignUlid('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignUlid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignUlid('zone_id')->nullable()->constrained('module_property_zones')->nullOnDelete();
                $table->string('title');
                $table->string('listing_type', 16)->default('sale');
                $table->string('status', 24)->default('available');
                $table->string('furnished', 24)->nullable();
                $table->unsignedInteger('price_qar')->default(0);
                $table->unsignedInteger('area_sqm')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('module_property_listings');
        Schema::dropIfExists('module_property_zones');
    }
};
