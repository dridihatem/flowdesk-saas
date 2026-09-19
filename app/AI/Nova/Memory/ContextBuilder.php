<?php

namespace App\AI\Nova\Memory;

use App\AI\Nova\Agent\NovaContext;
use App\AI\Nova\Tools\ToolRegistry;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Nova\NovaConversation;
use App\Models\User;
use Illuminate\Http\Request;

class ContextBuilder
{
    public function __construct(
        private ConversationMemory $memory,
        private ToolRegistry $tools,
    ) {}

    /**
     * @param  array{page?: string|null, entity?: array{type: string, id: string}|null}|null  $pageContext
     */
    public function build(User $user, Company $company, ?NovaConversation $conversation = null, ?array $pageContext = null, ?Request $request = null): NovaContext
    {
        $settings = CompanySetting::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        $aiAgent = is_array($settings?->ai_agent) ? $settings->ai_agent : [];
        $permissions = $user->getAllPermissions()->pluck('name')->values()->all();
        $roles = $user->getRoleNames()->values()->all();
        $role = (string) ($roles[0] ?? 'team_member');

        $recent = $conversation ? $this->memory->recent($conversation) : [];

        $page = $pageContext['page'] ?? $request?->input('current_page');
        $entity = $pageContext['entity'] ?? $request?->input('current_entity');
        if (is_array($entity) && isset($entity['type'], $entity['id'])) {
            $entity = ['type' => (string) $entity['type'], 'id' => (string) $entity['id']];
        } else {
            $entity = null;
        }

        $available = array_keys($this->tools->forContext(new NovaContext(
            companyId: (string) $company->id,
            userId: (int) $user->id,
            userRole: $role,
            permissions: $permissions,
            currentPage: is_string($page) ? $page : null,
            currentEntity: $entity,
            conversationId: $conversation?->id,
            language: (string) ($user->locale ?: app()->getLocale() ?: 'en'),
            currency: flowdesk_normalize_currency_code($company->default_currency ?? 'USD'),
            timezone: (string) (config('app.timezone') ?: 'UTC'),
            currentDate: now()->toDateString(),
            recentMessages: $recent,
            availableTools: [],
            company: $company,
            user: $user,
            allowAutoSendInvoices: (bool) ($aiAgent['allow_auto_send_invoices'] ?? false),
            assignmentMode: (string) ($aiAgent['assignment_mode'] ?? 'suggest'),
            autopilotMode: (string) ($aiAgent['autopilot_mode'] ?? 'manual'),
        )));

        return new NovaContext(
            companyId: (string) $company->id,
            userId: (int) $user->id,
            userRole: $role,
            permissions: $permissions,
            currentPage: is_string($page) ? $page : null,
            currentEntity: $entity,
            conversationId: $conversation?->id,
            language: (string) ($user->locale ?: app()->getLocale() ?: 'en'),
            currency: flowdesk_normalize_currency_code($company->default_currency ?? 'USD'),
            timezone: (string) (config('app.timezone') ?: 'UTC'),
            currentDate: now()->toDateString(),
            recentMessages: $recent,
            availableTools: $available,
            company: $company,
            user: $user,
            allowAutoSendInvoices: (bool) ($aiAgent['allow_auto_send_invoices'] ?? false),
            assignmentMode: (string) ($aiAgent['assignment_mode'] ?? 'suggest'),
            autopilotMode: (string) ($aiAgent['autopilot_mode'] ?? 'manual'),
        );
    }
}
