<?php

namespace App\Support;

use App\Models\EmailMarketingTemplateModel;

final class EmailMarketingTemplateLibrary
{
    /**
     * Active platform-standard models (managed in admin, stored in email_marketing_template_models).
     *
     * @return array<string, array{name: string, category: string, body_html: string}>
     */
    public static function models(): array
    {
        return EmailMarketingTemplateModel::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (EmailMarketingTemplateModel $m) => [
                $m->slug => [
                    'name' => $m->name,
                    'category' => $m->category ?? '',
                    'body_html' => $m->body_html ?? '',
                ],
            ])
            ->all();
    }

    public static function model(string $key): ?array
    {
        $m = EmailMarketingTemplateModel::query()
            ->where('slug', $key)
            ->where('is_active', true)
            ->first();

        if ($m === null) {
            return null;
        }

        return [
            'name' => $m->name,
            'category' => $m->category ?? '',
            'body_html' => $m->body_html ?? '',
        ];
    }
}
