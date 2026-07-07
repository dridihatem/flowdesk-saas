<?php

use App\Support\EmailMarketingTemplateModelDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_marketing_template_models')) {
            return;
        }

        $models = EmailMarketingTemplateModelDefaults::definitions();

        $maxSort = (int) (DB::table('email_marketing_template_models')->max('sort_order') ?? 0);
        $order = $maxSort;

        foreach ($models as $slug => $row) {
            if (DB::table('email_marketing_template_models')->where('slug', $slug)->exists()) {
                continue;
            }

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
        //
    }
};
