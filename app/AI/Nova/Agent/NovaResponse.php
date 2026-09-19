<?php

namespace App\AI\Nova\Agent;

final class NovaResponse
{
    /**
     * @param  list<array<string, mixed>>  $activities
     * @param  list<array{id: string, label: string}>|null  $clarificationOptions
     * @param  array<string, mixed>|null  $confirmation
     * @param  array<string, mixed>|null  $projectPreview
     * @param  array<string, mixed>|null  $assignmentPreview
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly string $runId,
        public readonly ?string $conversationId = null,
        public readonly array $activities = [],
        public readonly ?array $clarificationOptions = null,
        public readonly ?array $confirmation = null,
        public readonly ?array $projectPreview = null,
        public readonly ?array $assignmentPreview = null,
        public readonly ?array $data = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'message' => $this->message,
            'run_id' => $this->runId,
            'conversation_id' => $this->conversationId,
            'activities' => $this->activities,
            'clarification_options' => $this->clarificationOptions,
            'confirmation' => $this->confirmation,
            'project_preview' => $this->projectPreview,
            'assignment_preview' => $this->assignmentPreview,
            'data' => $this->data,
        ], fn ($v) => $v !== null);
    }
}
