<?php

namespace App\AI\Nova\Agent;

final class NovaDecision
{
    /**
     * @param  list<array{tool: string, arguments: array<string, mixed>}>  $toolCalls
     * @param  list<array{id: string, label: string}>|null  $clarificationOptions
     * @param  array<string, mixed>|null  $pendingConfirmation
     */
    public function __construct(
        public readonly string $type,
        public readonly ?string $message = null,
        public readonly array $toolCalls = [],
        public readonly ?array $clarificationOptions = null,
        public readonly ?array $pendingConfirmation = null,
    ) {}

    public static function respond(string $message): self
    {
        return new self(type: 'respond', message: $message);
    }

    /**
     * @param  list<array{tool: string, arguments: array<string, mixed>}>  $toolCalls
     */
    public static function useTools(array $toolCalls, ?string $message = null): self
    {
        return new self(type: 'tools', message: $message, toolCalls: $toolCalls);
    }

    /**
     * @param  list<array{id: string, label: string}>  $options
     */
    public static function clarify(string $message, array $options = []): self
    {
        return new self(type: 'clarify', message: $message, clarificationOptions: $options);
    }

    /**
     * @param  array<string, mixed>  $pending
     */
    public static function confirm(string $message, array $pending): self
    {
        return new self(type: 'confirm', message: $message, pendingConfirmation: $pending);
    }
}
