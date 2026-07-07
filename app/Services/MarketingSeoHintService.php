<?php

namespace App\Services;

class MarketingSeoHintService
{
    /**
     * @param  array<string, mixed>  $marketing
     * @param  list<array{path: string, count: int, title: ?string}>  $topPaths
     * @return list<string>
     */
    public function build(
        array $marketing,
        int $sitePageviews,
        int $widgetViews,
        int $widgetSubmits,
        array $topPaths,
    ): array {
        $hints = [];

        if ($sitePageviews === 0) {
            $hints[] = __('marketing_seo_hint_install_tracker');
        }

        $websiteUrl = isset($marketing['website_url']) ? trim((string) $marketing['website_url']) : '';
        if ($websiteUrl === '') {
            $hints[] = __('marketing_seo_hint_save_website_url');
        } elseif (! str_starts_with(strtolower($websiteUrl), 'https://')) {
            $hints[] = __('marketing_seo_hint_use_https');
        }

        $ga = trim((string) ($marketing['google_analytics_measurement_id'] ?? ''));
        $gtm = trim((string) ($marketing['google_tag_manager_id'] ?? ''));
        if ($ga === '' && $gtm === '') {
            $hints[] = __('marketing_seo_hint_connect_ga');
        }

        if ($widgetViews > 10 && $widgetSubmits === 0) {
            $hints[] = __('marketing_seo_hint_form_no_submits');
        }

        foreach ($topPaths as $row) {
            $title = isset($row['title']) ? trim((string) $row['title']) : '';
            if ($title !== '' && mb_strlen($title) < 25) {
                $hints[] = __('marketing_seo_hint_title_too_short', ['path' => $row['path']]);
                break;
            }
        }

        if (count($topPaths) === 1 && ($topPaths[0]['path'] ?? '/') === '/') {
            $hints[] = __('marketing_seo_hint_expand_landing_pages');
        }

        $hints[] = __('marketing_seo_hint_search_console');

        return array_values(array_unique($hints));
    }
}
