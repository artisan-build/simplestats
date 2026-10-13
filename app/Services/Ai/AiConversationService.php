<?php

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Models\Conversation;

class AiConversationService
{
    public function __construct(protected ConversationStore $store) {}

    /**
     * Resolve the given conversation for the user, or start a new one titled
     * after the first message. Pre-creating (instead of letting the package's
     * RememberConversation middleware do it after the stream ends) makes the
     * conversation ID available as a response header before streaming starts.
     */
    public function resolveOrCreate(User $user, ?string $conversationId, string $message): string
    {
        if ($conversationId && $user->conversations()->whereKey($conversationId)->exists()) {
            return $conversationId;
        }

        return $this->store->storeConversation($user->id, Str::limit($message, 50, preserveWords: true));
    }

    /**
     * @return array{id: string, title: string, messages: array<int, array{role: string, content: string, created_at: string|null}>}
     */
    public function toPayload(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $conversation->messages()->oldest()
                ->get()
                ->filter(fn ($message): bool => in_array($message->role, ['user', 'assistant']) && trim($message->content) !== '')
                ->map(fn ($message): array => [
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
