<?php

use App\Models\EmailMarketingTemplateModel;
use App\Support\EmailMarketingTemplateModelDefaults;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (EmailMarketingTemplateModel::query()->where('slug', 'corporate_blue_branded')->exists()) {
            return;
        }

        $definitions = EmailMarketingTemplateModelDefaults::definitions();
        $def = $definitions['corporate_blue_branded'] ?? null;
        if (! is_array($def)) {
            return;
        }

        $maxSort = (int) EmailMarketingTemplateModel::query()->max('sort_order');

        EmailMarketingTemplateModel::query()->create([
            'slug' => 'corporate_blue_branded',
            'name' => $def['name'],
            'category' => $def['category'],
            'body_html' => $def['body_html'],
            'is_active' => true,
            'sort_order' => $maxSort + 1,
        ]);
    }

    public function down(): void
    {
        EmailMarketingTemplateModel::query()->where('slug', 'corporate_blue_branded')->delete();
    }
};
