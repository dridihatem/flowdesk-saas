<?php

namespace App\AI\Nova\Memory;

use App\Models\Nova\NovaConversation;
use App\Models\Nova\NovaMessage;

class ConversationMemory
{
    /**
     * @return list<array{role: string, content: string}>
     */
    public function recent(NovaConversation $conversation, int $limit = 20): array
    {
        return $conversation->messages()
            ->latest()
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->values()
            ->map(fn (NovaMessage $m) => [
                'role' => (string) $m->role,
                'content' => (string) $m->content,
            ])
            ->all();
    }

    public function append(NovaConversation $conversation, string $role, string $content, ?array $metadata = null): NovaMessage
    {
        if (! in_array($role, NovaMessage::ROLES, true)) {
            throw new \InvalidArgumentException("Invalid Nova message role: {$role}");
        }

        return $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }
}
