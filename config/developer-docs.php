<?php

return [

    'stack' => [
        'PHP 8.3',
        'Laravel 13',
        'MySQL / MariaDB',
        'Vite + Tailwind CSS + Alpine.js',
        'Spatie Permission (roles)',
        'Stripe / PayPal / Flouci (payments)',
    ],

    'repo_guides' => [
        ['file' => 'MODULE.md', 'title' => 'Build installable modules', 'topic' => 'modules'],
        ['file' => 'NOVA.md', 'title' => 'Nova AI assistant', 'topic' => 'ai'],
        ['file' => 'tenancy.mdc', 'title' => 'Multi-tenancy rules', 'topic' => 'tenancy'],
        ['file' => 'auth-roles.mdc', 'title' => 'Auth & roles', 'topic' => 'auth'],
        ['file' => 'billing-plans.mdc', 'title' => 'Billing & plan limits', 'topic' => 'billing'],
        ['file' => 'database.mdc', 'title' => 'Database conventions', 'topic' => 'database'],
        ['file' => 'theme-engine.mdc', 'title' => 'Theme engine', 'topic' => 'themes'],
        ['file' => 'testing.mdc', 'title' => 'Testing (Pest)', 'topic' => 'testing'],
    ],

    'sections' => [
        [
            'id' => 'overview',
            'icon' => 'fa-house',
            'tree' => [
                'flowdesk-saas/' => [
                    'app/' => 'Application code (MVC, services, jobs)',
                    'bootstrap/' => 'Laravel bootstrap',
                    'config/' => 'Configuration (flowdesk.php, services, plans)',
                    'database/' => 'Migrations, factories, seeders',
                    'lang/' => 'Translations (en, fr, es, ar JSON)',
                    'public/' => 'Web root (index.php, build assets)',
                    'resources/' => 'Blade views, CSS, JavaScript',
                    'routes/' => 'web.php, api.php, auth.php',
                    'storage/' => 'Logs, cache, module stubs, uploads',
                    'tests/' => 'Pest feature & unit tests',
                    'vendor/' => 'Composer dependencies',
                ],
            ],
        ],
        [
            'id' => 'app',
            'icon' => 'fa-code',
            'tree' => [
                'app/' => [
                    'Console/Commands/' => 'Scheduled & artisan commands',
                    'Enums/' => 'Backed enums (InvoiceStatus, TaskStatus, …)',
                    'Http/Controllers/' => 'HTTP controllers grouped by domain',
                    'Http/Controllers/Admin/' => 'Platform admin (companies, plans, settings)',
                    'Http/Controllers/Auth/' => 'Login, register, OAuth, 2FA',
                    'Http/Controllers/EmailMarketing/' => 'Campaigns, templates, sequences',
                    'Http/Controllers/Portal/' => 'Client portal',
                    'Http/Controllers/Provider/' => 'Business provider portal',
                    'Http/Controllers/Settings/' => 'Workspace settings hub',
                    'Http/Controllers/Webhooks/' => 'Stripe, Flouci webhooks',
                    'Http/Middleware/' => 'Tenant, plan gates, 2FA, locale',
                    'Http/Requests/' => 'Form request validation classes',
                    'Jobs/' => 'Queued jobs (Google Calendar sync, …)',
                    'Mail/' => 'Mailable classes',
                    'Models/' => 'Eloquent models',
                    'Models/Concerns/TenantScope.php' => 'Auto company_id scope',
                    'Observers/' => 'Model observers',
                    'Providers/' => 'Service providers',
                    'Services/' => 'Business logic (keep controllers thin)',
                    'Support/' => 'Small helpers (DB utilities)',
                    'View/Components/' => 'Blade components (x-flow.*, x-admin-layout)',
                    'helpers.php' => 'Global helper functions',
                ],
            ],
        ],
        [
            'id' => 'routes',
            'icon' => 'fa-route',
            'tree' => [
                'routes/web.php' => [
                    'Central (no tenant)' => 'Marketing, register, admin /admin/*',
                    'tenant.match middleware' => 'Subdomain resolves Company',
                    'workspace.staff' => 'Company admin & team members',
                    'plan.feature:*' => 'Plan limit gates (analytics, ai_credits, …)',
                    'Portal routes' => '/portal/* client self-service',
                    'Provider routes' => '/provider/* partner workflows',
                ],
                'routes/api.php' => 'Embed forms, tracking, API tokens',
                'routes/auth.php' => 'Breeze auth + admin login',
            ],
        ],
        [
            'id' => 'tenancy',
            'icon' => 'fa-building',
            'tree' => [
                'Tenant resolution' => [
                    'ResolveTenant middleware' => 'Host → subdomain → Company',
                    'EnsureUserBelongsToTenant' => 'User must match current company',
                    'TenantScope trait' => 'Auto-filter queries by company_id',
                ],
                'Global models (no tenant scope)' => 'Company, Plan, PlanLimit, PlatformSetting, CurrencyRate',
                'Tenant models' => 'Client, Project, Invoice, Payment, User (workspace), Form, …',
            ],
        ],
        [
            'id' => 'ai',
            'icon' => 'fa-wand-magic-sparkles',
            'tree' => [
                'Thinking (LLM)' => [
                    'AiAssistantController' => 'Chat, speak, briefing endpoints',
                    'NovaAssistantService' => 'Workspace Q&A routing',
                    'AnthropicClaudeService' => 'Claude API',
                    'OpenAiTextToSpeechService' => 'OpenAI chat (via LLM router)',
                    'GoogleGeminiService' => 'Gemini chat & document scan',
                    'AiCreditUsageService' => 'Per-task credit billing',
                    'config/flowdesk.php → ai_task_credits' => 'Credit costs per AI action',
                ],
                'Voice (TTS)' => [
                    'NovaTextToSpeechService' => 'Routes Edge → Gemini → OpenAI',
                    'MicrosoftEdgeTextToSpeechService' => 'Free neural TTS (FR/EN/ES/AR)',
                    'GoogleGeminiTextToSpeechService' => 'Premium TTS (paid plan)',
                    'OpenAiTextToSpeechService' => 'Premium TTS (paid plan)',
                    'resources/js/flowdesk-nova-speech.js' => 'Browser playback + fallback',
                    'resources/js/nova-voice-nav.js' => 'Wake word & navigation',
                ],
                'Admin keys' => 'Platform settings → AI thinking + voice providers',
            ],
        ],
        [
            'id' => 'modules',
            'icon' => 'fa-puzzle-piece',
            'tree' => [
                'MODULE.md' => 'Full module authoring guide',
                'storage/stubs/' => 'Sample module zips & templates',
                'ModulePageController' => 'Renders installed module views',
                'ModuleActionController' => 'Module CRUD actions',
                'MarketplaceModuleZipService' => 'Zip validation & install',
                'resources/views/modules/' => 'Module fallback templates',
            ],
        ],
        [
            'id' => 'billing',
            'icon' => 'fa-credit-card',
            'tree' => [
                'PlanLimitService' => 'Feature gates & quotas',
                'Subscription / Plan / PlanLimit models' => 'Plan definitions',
                'BillingController' => 'Workspace billing UI',
                'StripeWebhookController' => 'Subscription lifecycle',
                'ShareWorkspacePlanContext middleware' => 'Shares plan gates to views',
            ],
        ],
        [
            'id' => 'frontend',
            'icon' => 'fa-paintbrush',
            'tree' => [
                'resources/js/' => [
                    'app.js' => 'Alpine stores, invoice/quote forms',
                    'flowdesk-nova-speech.js' => 'Nova TTS playback',
                    'flowdesk-notify.js' => 'Toast notifications',
                    'ai-voice.js' => 'Voice dictation on documents',
                    'nova-assistant.js' => 'Nova chat panel',
                ],
                'resources/css/app.css' => 'Tailwind entry',
                'resources/views/components/' => 'Reusable Blade components',
                'resources/views/layouts/' => 'Workspace & admin layouts',
                'vite.config.js' => 'Vite build config',
            ],
        ],
        [
            'id' => 'database',
            'icon' => 'fa-database',
            'tree' => [
                'database/migrations/' => 'Schema versions (timestamped)',
                'database/seeders/' => 'RoleSeeder, PlanSeeder, …',
                'database/factories/' => 'Test data factories',
                'Key tables' => [
                    'companies' => 'Tenant root (subdomain)',
                    'users' => 'Workspace staff, portal users, platform_admin',
                    'clients, projects, invoices' => 'Core CRM',
                    'platform_settings' => 'Global AI keys, TTS, rates',
                    'plans, plan_limits, subscriptions' => 'SaaS billing',
                    'installed_modules' => 'Per-workspace modules',
                ],
            ],
        ],
        [
            'id' => 'testing',
            'icon' => 'fa-vial',
            'tree' => [
                'tests/Feature/' => 'HTTP & integration tests (Pest)',
                'tests/Unit/' => 'Isolated unit tests',
                'php artisan test' => 'Run full suite',
                'php artisan test --filter=Nova' => 'Filter by name',
            ],
        ],
    ],

    'workflows' => [
        [
            'title' => 'Add a workspace feature',
            'steps' => [
                'Migration + Model (use TenantScope if tenant-owned)',
                'Service class for business logic',
                'Controller + Form Request',
                'Route in web.php with correct middleware (tenant, plan.feature)',
                'Blade view under resources/views/',
                'Translations in lang/*.json',
                'Feature test in tests/Feature/',
            ],
        ],
        [
            'title' => 'Add an admin platform setting',
            'steps' => [
                'Migration on platform_settings if new column',
                'PlatformSettingsController validation + save',
                'admin/platform-settings.blade.php form field',
                'Service that reads PlatformSetting::query()->first()',
            ],
        ],
        [
            'title' => 'Gate a feature by plan',
            'steps' => [
                'Add feature_key to PlanLimitService::FEATURE_KEYS',
                'Seed plan_limits row (0 = off, null = unlimited)',
                'Route middleware: plan.feature:your_key',
                'Check PlanLimitService::isFeatureEnabled() in services',
            ],
        ],
    ],

];
