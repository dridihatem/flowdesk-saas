<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central application hostnames
    |--------------------------------------------------------------------------
    |
    | Requests whose Host header matches one of these values are treated as the
    | main (non-tenant) site: marketing, registration, billing portal, etc.
    |
    */
    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FLOWDESK_CENTRAL_DOMAINS', '127.0.0.1,localhost'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Tenant base domain
    |--------------------------------------------------------------------------
    |
    | When set (e.g. flowdesk-saas.com), the first label is the tenant subdomain.
    | Leave null for local development where you rely on *.test hosts only.
    |
    */
    'tenant_base_domain' => env('FLOWDESK_TENANT_BASE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Supported UI locales
    |--------------------------------------------------------------------------
    */

    'locales' => ['en', 'fr', 'es', 'ar', 'id', 'hi'],

    /*
    |--------------------------------------------------------------------------
    | New workspace free trial
    |--------------------------------------------------------------------------
    */

    'trial_days' => (int) env('FLOWDESK_TRIAL_DAYS', 14),
    'trial_plan_slug' => env('FLOWDESK_TRIAL_PLAN_SLUG', 'pro'),

    /*
    |--------------------------------------------------------------------------
    | Platform administrator (central app only; manages companies & plans)
    |--------------------------------------------------------------------------
    */

    'platform_admin_email' => env('FLOWDESK_PLATFORM_ADMIN_EMAIL', 'platform@demo.local'),

    /*
    |--------------------------------------------------------------------------
    | Public contact form (marketing site)
    |--------------------------------------------------------------------------
    |
    | When set to a valid email, POST /contact will attempt to send mail there.
    | Messages are always logged. Set MAIL_MAILER to smtp, resend, postmark, etc.
    | (not `log`) for real delivery. Partnership invites, invoices, and marketing
    | Mailables use ShouldQueue: use QUEUE_CONNECTION=sync to send in-process, or
    | run `php artisan queue:work` when using database/redis queues.
    |
    */

    'contact_inbox_email' => env('FLOWDESK_CONTACT_INBOX_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Default currency by ISO 3166-1 alpha-2 country (company registration)
    |--------------------------------------------------------------------------
    */

    'country_currency' => [
        'TN' => 'TND',
        'QA' => 'QAR',
        'US' => 'USD',
        'FR' => 'EUR',
        'ES' => 'EUR',
        'GB' => 'GBP',
        'DE' => 'EUR',
        'CA' => 'CAD',
        'AE' => 'AED',
        'SA' => 'SAR',
        'KW' => 'KWD',
        'BH' => 'BHD',
        'OM' => 'OMR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default UI locale by ISO 3166-1 alpha-2 country (IP / registration)
    |--------------------------------------------------------------------------
    */

    'country_locale' => [
        'FR' => 'fr', 'BE' => 'fr', 'CH' => 'fr', 'LU' => 'fr', 'MC' => 'fr', 'SN' => 'fr', 'CI' => 'fr', 'MA' => 'fr', 'TN' => 'fr', 'DZ' => 'ar',
        'ES' => 'es', 'MX' => 'es', 'AR' => 'es', 'CO' => 'es', 'CL' => 'es', 'PE' => 'es', 'VE' => 'es', 'EC' => 'es', 'GT' => 'es', 'CU' => 'es',
        'SA' => 'ar', 'AE' => 'ar', 'QA' => 'ar', 'KW' => 'ar', 'BH' => 'ar', 'OM' => 'ar', 'EG' => 'ar', 'JO' => 'ar', 'LB' => 'ar', 'IQ' => 'ar', 'SY' => 'ar', 'YE' => 'ar', 'LY' => 'ar', 'PS' => 'ar', 'SD' => 'ar',
        'IN' => 'hi',
        'ID' => 'id',
        'US' => 'en', 'GB' => 'en', 'AU' => 'en', 'NZ' => 'en', 'IE' => 'en', 'CA' => 'en', 'ZA' => 'en', 'NG' => 'en', 'KE' => 'en', 'SG' => 'en', 'PH' => 'en',
        'DE' => 'en', 'IT' => 'en', 'NL' => 'en', 'PT' => 'en', 'PL' => 'en', 'SE' => 'en', 'NO' => 'en', 'DK' => 'en', 'FI' => 'en', 'AT' => 'en',
    ],

    /*
    |--------------------------------------------------------------------------
    | IP geolocation for locale (ip-api.com, no key; disable in local/tests)
    |--------------------------------------------------------------------------
    */

    'ip_locale_lookup' => (bool) env('FLOWDESK_IP_LOCALE_LOOKUP', true),

    /*
    |--------------------------------------------------------------------------
    | Default VAT / TVA rate (%) by ISO 3166-1 alpha-2 country
    |--------------------------------------------------------------------------
    |
    | Full map: config/flowdesk_country_vat.php (all registration countries).
    |
    */

    'country_vat' => require __DIR__.'/flowdesk_country_vat.php',

    /*
    |--------------------------------------------------------------------------
    | Document currencies (invoices, proposals, workspace default)
    |--------------------------------------------------------------------------
    |
    | ISO 4217 codes offered in selects. Add codes here to expose them in UI.
    | Existing records with a code not listed remain valid via validation merge.
    |
    */

    'supported_currencies' => [
        'USD', 'EUR', 'GBP', 'TND', 'QAR', 'CAD', 'AED', 'SAR',
    ],

    'currency_labels' => [
        'USD' => 'USD — US Dollar',
        'EUR' => 'EUR — Euro',
        'GBP' => 'GBP — British Pound',
        'TND' => 'TND — Tunisian Dinar',
        'QAR' => 'QAR — Qatari Riyal',
        'CAD' => 'CAD — Canadian Dollar',
        'AED' => 'AED — UAE Dirham',
        'SAR' => 'SAR — Saudi Riyal',
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform USD → quote rates (admin platform settings)
    |--------------------------------------------------------------------------
    |
    | Quote currencies managed in Admin → Platform settings. USD is always base.
    |
    */

    'platform_exchange_currencies' => [
        'QAR', 'TND', 'EUR', 'GBP', 'CAD', 'AED', 'SAR',
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketing modules — regions, default currency, and country filters
    |--------------------------------------------------------------------------
    */

    'marketing_regions' => [
        'global' => [
            'label_key' => 'marketing_region.global',
            'currency' => 'USD',
        ],
        'gcc' => [
            'label_key' => 'marketing_region.gcc',
            'currency' => 'QAR',
            'countries' => ['QA', 'AE', 'SA', 'KW', 'BH', 'OM'],
        ],
        'tunisia' => [
            'label_key' => 'marketing_region.tunisia',
            'currency' => 'TND',
            'countries' => ['TN'],
        ],
        'europe' => [
            'label_key' => 'marketing_region.europe',
            'currency' => 'EUR',
            'countries' => ['FR', 'DE', 'ES', 'GB', 'IT', 'BE', 'NL', 'PT', 'CH', 'AT'],
        ],
        'americas' => [
            'label_key' => 'marketing_region.americas',
            'currency' => 'USD',
            'countries' => ['US', 'CA'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Countries assignable to marketplace modules (admin multi-select)
    |--------------------------------------------------------------------------
    */

    'marketplace_module_countries' => [
        'QA', 'AE', 'SA', 'KW', 'BH', 'OM', 'TN', 'FR', 'DE', 'ES', 'GB', 'US', 'CA',
    ],

    /*
    |--------------------------------------------------------------------------
    | Integer scale per currency (stored amount × scale = display unit)
    |--------------------------------------------------------------------------
    |
    | TND: 1000 — millimes (1250500 stored = 1250.500 TND). USD/EUR/GBP: 100 (cents).
    | Currencies omitted here use 100.
    |
    */

    'currency_minor_scale' => [
        'TND' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice payment quick-fill (percent of invoice total, capped to balance)
    |--------------------------------------------------------------------------
    */

    'invoice_payment_quick_presets' => [
        ['percent' => 20],
        ['percent' => 25],
        ['percent' => 50],
        ['percent' => 75],
        ['percent' => 80],
        ['percent' => 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard DNS (production)
    |--------------------------------------------------------------------------
    |
    | Point *.flowdesk-saas.com to your app server, set FLOWDESK_TENANT_BASE_DOMAIN
    | to flowdesk-saas.com, and set SESSION_DOMAIN=.flowdesk-saas.com so session
    | cookies are shared between app and tenant hosts when you need SSO.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | UI theme (per company, stored in company_settings.theme JSON)
    |--------------------------------------------------------------------------
    */

    'theme_defaults' => [
        'theme_name' => 'default',
        'layout_type' => 'sidebar',
        'primary_color' => '#4f46e5',
        'secondary_color' => '#64748b',
        'font_family' => 'Figtree',
        'dark_mode' => 'system',
        'logo_path' => null,
        'custom_css' => null,
    ],

    'theme_presets' => [
        'default' => [
            'primary_color' => '#2563eb',
            'secondary_color' => '#64748b',
        ],
        'dark_pro' => [
            'primary_color' => '#38bdf8',
            'secondary_color' => '#94a3b8',
        ],
        'minimal' => [
            'primary_color' => '#18181b',
            'secondary_color' => '#71717a',
        ],
        'luxury' => [
            'primary_color' => '#c4a962',
            'secondary_color' => '#1e293b',
        ],
        'admin' => [
            'primary_color' => '#dc2626',
            'secondary_color' => '#0f172a',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Project & task attachments (storage on public disk under project-files / project-task-files)
    |--------------------------------------------------------------------------
    */

    'project_files' => [
        'max_file_kb' => (int) env('FLOWDESK_PROJECT_MAX_FILE_KB', 12288),
        'max_storage_mb_per_project' => (int) env('FLOWDESK_PROJECT_MAX_STORAGE_MB', 512),
        'thumb_max_width' => (int) env('FLOWDESK_PROJECT_THUMB_MAX_WIDTH', 360),
        'thumb_jpeg_quality' => (int) env('FLOWDESK_PROJECT_THUMB_JPEG_QUALITY', 82),
    ],

    /*
    |--------------------------------------------------------------------------
    | Product brand (user-facing name & tagline)
    |--------------------------------------------------------------------------
    */
    'brand_name' => env('FLOWDESK_BRAND_NAME', 'Flowqil'),
    'brand_tagline' => env('FLOWDESK_BRAND_TAGLINE', 'The brain that flows with your voice.'),

    /*
    |--------------------------------------------------------------------------
    | AI business assistant (Nova)
    |--------------------------------------------------------------------------
    */
    'ai_assistant_brand_name' => env('FLOWDESK_AI_ASSISTANT_NAME', 'Nova'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard widgets (keys map to resources/views/dashboard/widgets/*.blade.php)
    |--------------------------------------------------------------------------
    */

    'dashboard_widgets' => [
        'registration_notice' => [
            'label' => 'Registration notice',
        ],
        'kpi_grid' => [
            'label' => 'KPI stats & Nova',
        ],
        'provider_commissions' => [
            'label' => 'Provider commissions',
        ],
        'projects_pipeline' => [
            'label' => 'Projects & deadlines',
        ],
        'dashboard_charts' => [
            'label' => 'Invoice & revenue trends',
        ],
        'company_cards' => [
            'label' => 'Company cards',
        ],
        'metrics_table' => [
            'label' => 'Workspace table',
        ],
        'vue_pulse' => [
            'label' => 'Pulse widget (Vue)',
        ],
        'ai_assistant' => [
            'label' => 'Nova AI insights',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova TTS locale voices (Microsoft Edge — free, no API key)
    |--------------------------------------------------------------------------
    */
    'nova_tts_voices' => [
        'edge' => [
            'fr' => 'fr-FR-DeniseNeural',
            'en' => 'en-US-JennyNeural',
            'es' => 'es-ES-ElviraNeural',
            'ar' => 'ar-EG-SalmaNeural',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI writing modes (Nova → Writing tools)
    |--------------------------------------------------------------------------
    */
    'landing_page_writing_mode_enabled' => filter_var(
        env('FLOWDESK_LANDING_PAGE_WRITING_MODE', false),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | AI task credits (plan quota): fixed cost per task by complexity
    |--------------------------------------------------------------------------
    | Each AI action bills a flat number of credits from the workspace monthly quota.
    | Assistant modes can override the assistant.default cost.
    */
    'ai_task_credits' => [
        'assistant' => [
            'default' => 50,
            'modes' => [
                'proposal' => 80,
                'pricing' => 80,
                'form' => 60,
                'summary' => 50,
                'ticket' => 40,
                'client_email' => 60,
                'task_followup' => 50,
                'seo' => 100,
                'project_description' => 80,
                'growth_projects' => 100,
                'growth_invoices' => 100,
                'growth_clients' => 100,
                'report_counsel' => 100,
                'landing_page' => 150,
                'nova_chat' => 75,
                'nova_voice' => 5,
                'nova_briefing' => 15,
            ],
        ],
        'report_counsel' => 100,
        'project_workflow' => 150,
        'project_example_workspace' => 250,
        'email_template' => 120,
        'email_campaign_content' => 150,
        'quote_line_items' => 120,
        'invoice_line_items' => 120,
        'quote_line_items_scan' => 150,
        'invoice_line_items_scan' => 150,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pay-per-use (estimated charges; configure settlement in your billing process)
    |--------------------------------------------------------------------------
    */
    'pay_per_use' => [
        'ai_credit_price_minor' => (int) env('FLOWDESK_AI_CREDIT_PRICE_MINOR', 1),
    ],

    'font_urls' => [
        'Figtree' => 'https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap',
        'Inter' => 'https://fonts.bunny.net/css?family=inter:400,500,600&display=swap',
        'DM Sans' => 'https://fonts.bunny.net/css?family=dm-sans:400,500,600&display=swap',
        'Nunito' => 'https://fonts.bunny.net/css?family=nunito:400,500,600&display=swap',
        'Source Sans 3' => 'https://fonts.bunny.net/css?family=source-sans-3:400,600&display=swap',
    ],

];
