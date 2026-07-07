<?php

namespace App\Services;

use App\Models\ChatThread;
use App\Models\Client;
use App\Models\User;

class ChatThreadService
{
    public function clientThreadFor(Client $client): ChatThread
    {
        $thread = ChatThread::query()->firstOrCreate(
            [
                'company_id' => $client->company_id,
                'type' => ChatThread::TYPE_CLIENT,
                'subject_id' => $client->id,
            ],
        );

        if ($client->user_id) {
            $this->addParticipant($thread, (int) $client->user_id);
        }

        return $thread;
    }

    public function addParticipant(ChatThread $thread, int $userId): void
    {
        $thread->participants()->syncWithoutDetaching([$userId]);
    }

    public function userCanAccess(ChatThread $thread, User $user): bool
    {
        if ((string) $thread->company_id !== (string) $user->company_id) {
            return false;
        }

        if ($user->hasAnyRole(['company_admin', 'team_member'])) {
            return true;
        }

        if ($thread->participants()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($user->hasRole('client')) {
            $clientId = $user->clientProfile?->id;

            return $clientId
                && $thread->type === ChatThread::TYPE_CLIENT
                && (string) $thread->subject_id === (string) $clientId;
        }

        if ($user->hasRole('business_provider')) {
            $providerId = $user->providerProfile?->id;

            return $providerId
                && $thread->type === ChatThread::TYPE_PROVIDER
                && (string) $thread->subject_id === (string) $providerId;
        }

        return false;
    }
}
