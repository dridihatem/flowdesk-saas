<?php

use App\Support\EmailMarketingTemplateModelDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_marketing_template_models', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->longText('body_html');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $models = EmailMarketingTemplateModelDefaults::definitions();
        $order = 0;
        foreach ($models as $slug => $row) {
            $order += 10;
            DB::table('email_marketing_template_models')->insert([
                'id' => (string) Str::ulid(),
                'slug' => $slug,
                'name' => $row['name'] ?? $slug,
                'category' => $row['category'] ?? null,
                'body_html' => $row['body_html'] ?? '',
                'sort_order' => $order,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_marketing_template_models');
    }
};
