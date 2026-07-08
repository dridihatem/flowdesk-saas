<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;

class NovaVoiceNavigationService
{
    public function __construct(
        private WorkspaceNavigationService $navigation,
        private NovaVoiceBriefingService $briefing,
        private NovaVoiceWorkflowService $workflows,
        private NovaIdentityService $identity,
    ) {}

    /**
     * Voice navigation commands available to the current user.
     *
     * @return list<array{id: string, label: string, url: string, phrases: list<string>}>
     */
    public function commandsFor(User $user, array $gates): array
    {
        $allowedUrls = $this->allowedNavUrls($user, $gates);
        $commands = [];

        foreach ($this->allCommandDefinitions() as $definition) {
            if (! ($definition['visible'])($user, $gates)) {
                continue;
            }

            if (isset($definition['action']) && is_string($definition['action'])) {
                $command = $this->actionCommand($definition);
                if ($command['url'] !== '') {
                    $commands[] = $command;
                }

                continue;
            }

            if (! Route::has($definition['route'])) {
                continue;
            }

            $url = route($definition['route'], $definition['route_params'] ?? [], false);
            if ($definition['require_nav'] ?? true) {
                $path = '/'.ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
                if (! $this->urlAllowed($allowedUrls, $path)) {
                    continue;
                }
            }

            $commands[] = [
                'id' => $definition['id'],
                'label' => $this->commandLabel($definition),
                'url' => $this->commandUrl($url, $definition),
                'phrases' => $this->phrasesFor($definition['phrase_key']),
            ];
        }

        return $this->mergeCatalogCommands($user, $gates, $commands);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function commandUrl(string $url, array $definition): string
    {
        $full = url($url);

        if ($definition['voice_ai'] ?? false) {
            return $full.(str_contains($full, '?') ? '&' : '?').'nova_ai=1';
        }

        return $full;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{id: string, label: string, action: string, url: string, phrases: list<string>}
     */
    private function actionCommand(array $definition): array
    {
        $action = (string) $definition['action'];
        $url = match ($action) {
            'logout' => Route::has('logout') ? route('logout') : '',
            default => '',
        };

        return [
            'id' => (string) $definition['id'],
            'label' => $this->commandLabel($definition),
            'action' => $action,
            'url' => $url,
            'phrases' => $this->phrasesFor((string) $definition['phrase_key']),
        ];
    }

    /**
     * @param  list<array{id: string, label: string, url: string, phrases: list<string>}>  $commands
     * @return list<array{id: string, label: string, url: string, phrases: list<string>}>
     */
    private function mergeCatalogCommands(User $user, array $gates, array $commands): array
    {
        $seenRoutes = collect($this->allCommandDefinitions())
            ->pluck('route')
            ->filter(fn (mixed $route) => is_string($route) && $route !== '')
            ->flip();
        $allowedUrls = $this->allowedNavUrls($user, $gates);

        foreach ($this->navigation->catalog() as $key => $item) {
            $routeName = (string) ($item['route'] ?? '');
            $commandId = 'nav.'.$key;

            if ($routeName === '' || $seenRoutes->has($routeName)) {
                continue;
            }

            if (! ($item['visible'])($user, $gates) || ! Route::has($routeName)) {
                continue;
            }

            $url = route($routeName, [], false);
            $path = '/'.ltrim(parse_url($url, PHP_URL_PATH) ?: $url, '/');
            if (! $this->urlAllowed($allowedUrls, $path)) {
                continue;
            }

            $phraseKey = $this->catalogPhraseKey($key);
            $commands[] = [
                'id' => $commandId,
                'label' => ($item['label'])(),
                'url' => url($url),
                'phrases' => $this->phrasesFor($phraseKey),
            ];
            $seenRoutes->put($routeName, true);
        }

        return $commands;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function commandLabel(array $definition): string
    {
        if (isset($definition['label']) && is_string($definition['label'])) {
            return $definition['label'];
        }

        if (isset($definition['label_resolver']) && is_callable($definition['label_resolver'])) {
            return (string) $definition['label_resolver']();
        }

        return __((string) ($definition['label_key'] ?? ''));
    }

    private function catalogPhraseKey(string $catalogKey): string
    {
        return match ($catalogKey) {
            'em_overview' => 'email_marketing',
            'em_campaigns' => 'email_campaigns',
            'em_templates' => 'email_templates',
            'em_template_new' => 'email_templates_create',
            'em_audiences' => 'email_audiences',
            'em_sequences' => 'email_sequences',
            'provider_portal' => 'provider_portal',
            default => str_replace('-', '_', $catalogKey),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function allCommandDefinitions(): array
    {
        return array_merge($this->commandDefinitions(), $this->actionCommandDefinitions());
    }

    /**
     * Voice shortcuts for primary buttons (create forms, inbox actions, etc.).
     *
     * @return list<array<string, mixed>>
     */
    private function actionCommandDefinitions(): array
    {
        $staff = fn (User $u) => $u->hasAnyRole(['company_admin', 'team_member']);
        $canProviders = fn (User $u, array $g) => ($g['providers'] ?? true) && $u->can('workspace.manage_providers');

        return [
            ['id' => 'providers.create', 'route' => 'providers.create', 'label_key' => 'Add provider', 'phrase_key' => 'providers_create', 'visible' => $canProviders, 'require_nav' => false],
            ['id' => 'clients.account-requests.index', 'route' => 'clients.account-requests.index', 'label_key' => 'Client signup requests', 'phrase_key' => 'client_signup_requests', 'visible' => fn (User $u) => $u->can('workspace.manage_clients'), 'require_nav' => false],
            ['id' => 'inquiries.create', 'route' => 'inquiries.create', 'label_key' => 'New inquiry', 'phrase_key' => 'inquiries_create', 'visible' => fn (User $u) => $u->can('workspace.manage_inquiries'), 'require_nav' => false],
            ['id' => 'forms.create', 'route' => 'forms.create', 'label_key' => 'New form', 'phrase_key' => 'forms_create', 'visible' => fn (User $u, array $g) => ($g['forms'] ?? true) && $staff($u) && $u->can('workspace.manage_projects'), 'require_nav' => false],
            ['id' => 'email-marketing.campaigns.create', 'route' => 'email-marketing.campaigns.create', 'label_key' => 'New campaign', 'phrase_key' => 'email_campaigns_create', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.templates.create', 'route' => 'email-marketing.templates.create', 'label_key' => 'email_marketing_nav_new_template', 'phrase_key' => 'email_templates_create', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.audiences.create', 'route' => 'email-marketing.audiences.create', 'label_key' => 'New audience', 'phrase_key' => 'email_audiences_create', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'providers.remittance-requests.index', 'route' => 'providers.remittance-requests.index', 'label_key' => 'provider_remittance_inbox_title', 'phrase_key' => 'provider_remittance', 'visible' => $canProviders, 'require_nav' => false],
            ['id' => 'settings.provider-recruitment', 'route' => 'settings.provider-recruitment', 'label_key' => 'Provider recruitment', 'phrase_key' => 'provider_recruitment', 'visible' => fn (User $u) => $u->hasRole('company_admin'), 'require_nav' => false],
            ['id' => 'logout', 'action' => 'logout', 'label_key' => 'Log Out', 'phrase_key' => 'logout', 'visible' => fn () => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function clientConfig(User $user, array $gates): array
    {
        $brand = (string) config('flowdesk.ai_assistant_brand_name', 'Nova');
        $tts = app(NovaTextToSpeechService::class)->publicConfig($user->company);
        $firstName = $this->userFirstName($user);
        $companyName = trim((string) ($user->company?->name ?? ''));
        $voiceCredits = app(AiCreditUsageService::class)->creditsForTask(
            AiCreditUsageService::TASK_ASSISTANT,
            'nova_voice',
        );
        $briefingCredits = app(AiCreditUsageService::class)->creditsForTask(
            AiCreditUsageService::TASK_ASSISTANT,
            'nova_briefing',
        );
        $chatCredits = app(AiCreditUsageService::class)->creditsForTask(
            AiCreditUsageService::TASK_ASSISTANT,
            'nova_chat',
        );

        return [
            'enabled' => (bool) ($gates['ai_credits'] ?? false),
            'brand' => $brand,
            'userName' => $firstName,
            'userId' => (string) $user->id,
            'companyName' => $companyName,
            'voiceCreditCost' => $voiceCredits,
            'briefingCreditCost' => $briefingCredits,
            'chatCreditCost' => $chatCredits,
            'appLocale' => app()->getLocale(),
            'speechLocale' => flowdesk_speech_recognition_locale(),
            'speakUrl' => route('assistant.speak'),
            'chatUrl' => route('assistant.chat'),
            'briefingUrl' => route('assistant.briefing'),
            'briefingRedirectUrl' => null,
            'tts' => $tts,
            'commands' => $this->commandsFor($user, $gates),
            'briefingPhrases' => $this->briefing->briefingPhrases(),
            'workflows' => $this->workflows->workflowsFor($user, $gates),
            'workflowUrl' => route('assistant.voice-workflow'),
            'labels' => [
                'listening' => __('nova_voice_listening'),
                'wake' => __('nova_voice_wake', ['name' => $brand]),
                'wakeReply' => __('nova_voice_wake_reply', [
                    'name' => $firstName !== '' ? $firstName : __('nova_voice_guest'),
                ]),
                'wakeReplyHello' => __('nova_voice_wake_reply_hello', [
                    'name' => $firstName !== '' ? $firstName : __('nova_voice_guest'),
                ]),
                'wakeReplyListening' => __('nova_voice_wake_reply_listening'),
                'identityReply' => $this->identity->reply($user->company, $user),
                'wakeTooltip' => __('nova_voice_wake_tooltip', [
                    'name' => $firstName !== '' ? $firstName : __('nova_voice_guest'),
                    'company' => $companyName !== '' ? $companyName : config('app.name'),
                ]),
                'creditsHint' => __('nova_voice_credits_hint', ['credits' => $voiceCredits]),
                'alwaysOn' => __('nova_voice_toujours_activee', ['name' => $brand]),
                'navigating' => __('nova_voice_navigating'),
                'loggingOut' => __('nova_voice_logging_out'),
                'briefing' => __('nova_voice_briefing'),
                'briefingError' => __('nova_voice_briefing_error'),
                'unknown' => __('nova_voice_unknown'),
                'thinking' => __('nova_voice_thinking'),
                'answering' => __('nova_voice_answering'),
                'chatListening' => __('nova_voice_chat_listening'),
                'browserFallback' => __('nova_voice_browser_fallback'),
                'notListening' => __('nova_voice_not_listening'),
                'listeningAgain' => __('nova_voice_listening_again'),
                'workflowListening' => __('nova_voice_workflow_listening'),
                'workflowError' => __('nova_workflow_error'),
                'stop' => __('nova_stop'),
                'unsupported' => __('ai_voice_unsupported'),
                'localeUnsupported' => __('ai_voice_locale_unsupported'),
            ],
        ];
    }

    private function userFirstName(User $user): string
    {
        $name = trim((string) $user->name);

        return $name === '' ? '' : explode(' ', $name, 2)[0];
    }

    /**
     * @return list<string>
     */
    private function phrasesFor(string $key): array
    {
        $groups = Lang::get('nova_voice.phrases');
        if (! is_array($groups)) {
            return [];
        }

        $phrases = $groups[$key] ?? null;
        if (! is_array($phrases)) {
            return [];
        }

        $normalized = [];
        foreach ($phrases as $phrase) {
            if (! is_scalar($phrase)) {
                continue;
            }

            $value = $this->normalizePhrase((string) $phrase);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizePhrase(string $phrase): string
    {
        $s = mb_strtolower(trim($phrase));
        $s = str_replace(['’', '`'], "'", $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return $s;
    }

    /**
     * @return list<string>
     */
    private function allowedNavUrls(User $user, array $gates): array
    {
        $urls = [];
        foreach ($this->navigation->sectionsFor($user, $gates) as $section) {
            foreach ($section['items'] as $item) {
                $path = parse_url($item['url'], PHP_URL_PATH);
                if (is_string($path) && $path !== '') {
                    $urls[] = rtrim($path, '/');
                }
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param  list<string>  $allowed
     */
    private function urlAllowed(array $allowed, string $path): bool
    {
        $path = rtrim($path, '/');
        foreach ($allowed as $allowedPath) {
            $allowedPath = rtrim($allowedPath, '/');
            if ($path === $allowedPath || str_starts_with($path, $allowedPath.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function commandDefinitions(): array
    {
        $staff = fn (User $u) => $u->hasAnyRole(['company_admin', 'team_member']);
        $canInvoices = fn (User $u) => $u->can('workspace.manage_invoices');
        $canClients = fn (User $u) => $u->can('workspace.manage_clients');
        $canProjects = fn (User $u, array $g) => ($g['projects'] ?? true) && $u->can('workspace.manage_projects');
        $canAnalytics = fn (User $u, array $g) => ($g['analytics'] ?? true) && $u->can('workspace.view_analytics');
        $canAi = fn (User $u, array $g) => ($g['ai_credits'] ?? true) && $u->can('workspace.view_dashboard');

        return [
            ['id' => 'dashboard', 'route' => 'dashboard', 'label_key' => 'Dashboard', 'phrase_key' => 'dashboard', 'visible' => fn (User $u) => $u->can('workspace.view_dashboard')],
            ['id' => 'calendar', 'route' => 'calendar.index', 'label_key' => 'Calendar', 'phrase_key' => 'calendar', 'visible' => fn (User $u, array $g) => ($g['calendar'] ?? true) && $u->can('workspace.view_dashboard')],
            ['id' => 'clients.index', 'route' => 'clients.index', 'label_key' => 'Clients', 'phrase_key' => 'clients', 'visible' => $canClients],
            ['id' => 'clients.create', 'route' => 'clients.create', 'label_key' => 'New client', 'phrase_key' => 'clients_create', 'visible' => $canClients, 'require_nav' => false],
            ['id' => 'projects.index', 'route' => 'projects.index', 'label_key' => 'Projects', 'phrase_key' => 'projects', 'visible' => $canProjects],
            ['id' => 'projects.create', 'route' => 'projects.create', 'label_key' => 'New project', 'phrase_key' => 'projects_create', 'visible' => $canProjects, 'require_nav' => false],
            ['id' => 'inquiries.index', 'route' => 'inquiries.index', 'label_key' => 'Inquiries', 'phrase_key' => 'inquiries', 'visible' => fn (User $u) => $u->can('workspace.manage_inquiries')],
            ['id' => 'proposals.index', 'route' => 'proposals.index', 'label_key' => 'Proposals', 'phrase_key' => 'proposals', 'visible' => $canInvoices],
            ['id' => 'proposals.create', 'route' => 'proposals.create', 'label_key' => 'New quote', 'phrase_key' => 'proposals_create', 'visible' => $canInvoices, 'require_nav' => false, 'voice_ai' => true],
            ['id' => 'invoices.index', 'route' => 'invoices.index', 'label_key' => 'Invoices', 'phrase_key' => 'invoices', 'visible' => $canInvoices],
            ['id' => 'invoices.create', 'route' => 'invoices.create', 'label_key' => 'New invoice', 'phrase_key' => 'invoices_create', 'visible' => $canInvoices, 'require_nav' => false, 'voice_ai' => true],
            ['id' => 'billing.index', 'route' => 'billing.index', 'label_key' => 'Plan subscription', 'phrase_key' => 'billing', 'visible' => fn (User $u) => $canInvoices($u) && $u->can('workspace.manage_subscription')],
            ['id' => 'analytics.index', 'route' => 'analytics.index', 'label_key' => 'Analytics', 'phrase_key' => 'analytics', 'visible' => $canAnalytics, 'require_nav' => false],
            ['id' => 'reports.index', 'route' => 'reports.index', 'label_key' => 'Reports', 'phrase_key' => 'reports', 'visible' => fn (User $u, array $g) => ($g['reports'] ?? true) && $u->can('workspace.view_analytics'), 'require_nav' => false],
            ['id' => 'marketing.hub', 'route' => 'marketing.hub', 'label_key' => 'Marketing', 'phrase_key' => 'marketing', 'visible' => fn (User $u, array $g) => ($g['marketing_hub'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'forms.index', 'route' => 'forms.index', 'label_key' => 'Lead forms', 'phrase_key' => 'forms', 'visible' => fn (User $u, array $g) => ($g['forms'] ?? true) && $staff($u) && $u->can('workspace.manage_projects'), 'require_nav' => false],
            ['id' => 'assistant.index', 'route' => 'assistant.index', 'label_key' => 'AI assistant', 'phrase_key' => 'assistant', 'visible' => $canAi, 'require_nav' => false],
            ['id' => 'notifications.index', 'route' => 'notifications.index', 'label_key' => 'Activity', 'phrase_key' => 'activity', 'visible' => fn () => true],
            ['id' => 'chat.index', 'route' => 'chat.index', 'label_key' => 'Messages', 'phrase_key' => 'messages', 'visible' => fn () => true],
            ['id' => 'tickets.index', 'route' => 'tickets.index', 'label_key' => 'Tickets', 'phrase_key' => 'tickets', 'visible' => fn () => true],
            ['id' => 'tickets.create', 'route' => 'tickets.create', 'label_key' => 'New ticket', 'phrase_key' => 'tickets_create', 'visible' => fn () => true, 'require_nav' => false],
            ['id' => 'providers.index', 'route' => 'providers.index', 'label_key' => 'Providers', 'phrase_key' => 'providers', 'visible' => fn (User $u, array $g) => ($g['providers'] ?? true) && $u->can('workspace.manage_providers'), 'require_nav' => false],
            ['id' => 'settings.workspace', 'route' => 'settings.workspace', 'label_key' => 'Settings', 'phrase_key' => 'settings', 'visible' => fn (User $u) => $staff($u), 'require_nav' => false],
            ['id' => 'settings.modules', 'route' => 'settings.modules', 'label_key' => 'Modules', 'phrase_key' => 'settings_modules', 'visible' => fn (User $u) => $staff($u), 'require_nav' => false],
            ['id' => 'settings.team', 'route' => 'settings.team', 'label_key' => 'Team', 'phrase_key' => 'settings_team', 'visible' => fn (User $u) => $staff($u), 'require_nav' => false],
            ['id' => 'settings.navigation', 'route' => 'settings.navigation', 'label_key' => 'Navigation', 'phrase_key' => 'settings_navigation', 'visible' => fn (User $u) => $staff($u), 'require_nav' => false],
            ['id' => 'settings.billing-tax', 'route' => 'settings.billing-tax', 'label_key' => 'Billing & tax', 'phrase_key' => 'settings_billing', 'visible' => fn (User $u) => $staff($u), 'require_nav' => false],
            ['id' => 'settings.security', 'route' => 'settings.security', 'label_key' => 'Security', 'phrase_key' => 'settings_security', 'visible' => fn (User $u) => $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.index', 'route' => 'email-marketing.index', 'label_key' => 'Overview', 'phrase_key' => 'email_marketing', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.campaigns.index', 'route' => 'email-marketing.campaigns.index', 'label_key' => 'Campaigns', 'phrase_key' => 'email_campaigns', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.templates.index', 'route' => 'email-marketing.templates.index', 'label_key' => 'Templates', 'phrase_key' => 'email_templates', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.audiences.index', 'route' => 'email-marketing.audiences.index', 'label_key' => 'Audiences', 'phrase_key' => 'email_audiences', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'email-marketing.sequences.index', 'route' => 'email-marketing.sequences.index', 'label_key' => 'Sequences', 'phrase_key' => 'email_sequences', 'visible' => fn (User $u, array $g) => ($g['email_marketing'] ?? true) && $staff($u), 'require_nav' => false],
            ['id' => 'profile.edit', 'route' => 'profile.edit', 'label_key' => 'Account', 'phrase_key' => 'profile', 'visible' => fn () => true, 'require_nav' => false],
        ];
    }
}
