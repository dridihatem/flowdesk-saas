<?php

namespace App\AI\Nova\Agent;

use App\Models\Company;
use App\Models\User;

final class NovaContext
{
    /**
     * @param  list<array{role: string, content: string}>  $recentMessages
     * @param  list<string>  $availableTools
     * @param  list<string>  $permissions
     * @param  array<string, mixed>|null  $currentEntity
     */
    public function __construct(
        public readonly string $companyId,
        public readonly int $userId,
        public readonly string $userRole,
        public readonly array $permissions,
        public readonly ?string $currentPage,
        public readonly ?array $currentEntity,
        public readonly ?string $conversationId,
        public readonly string $language,
        public readonly string $currency,
        public readonly string $timezone,
        public readonly string $currentDate,
        public readonly array $recentMessages,
        public readonly array $availableTools,
        public readonly Company $company,
        public readonly User $user,
        public readonly bool $allowAutoSendInvoices = false,
        public readonly string $assignmentMode = 'suggest',
        public readonly string $autopilotMode = 'manual',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'role' => $this->userRole,
            'permissions' => $this->permissions,
            'current_page' => $this->currentPage,
            'current_entity' => $this->currentEntity,
            'current_entity_type' => $this->currentEntity['type'] ?? null,
            'conversation_id' => $this->conversationId,
            'language' => $this->language,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'current_date' => $this->currentDate,
            'recent_messages' => $this->recentMessages,
            'available_tools' => $this->availableTools,
            'assignment_mode' => $this->assignmentMode,
            'autopilot_mode' => $this->autopilotMode,
        ];
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true) || $this->user->can($permission);
    }
}
