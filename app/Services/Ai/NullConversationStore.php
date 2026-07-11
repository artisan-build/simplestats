<?php

namespace App\Services\Ai;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Discards all conversation writes. Bound while impersonating (and in demo
 * mode), so the AI chat keeps working without leaving any trace in the
 * impersonated user's conversation history.
 */
class NullConversationStore implements ConversationStore
{
    public function latestConversationId(string|int $userId): ?string
    {
        return null;
    }

    public function storeConversation(string|int|null $userId, string $title): string
    {
        return (string) Str::uuid7();
    }

    public function storeUserMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt): string
    {
        return (string) Str::uuid7();
    }

    public function storeAssistantMessage(string $conversationId, string|int|null $userId, AgentPrompt $prompt, AgentResponse $response): string
    {
        return (string) Str::uuid7();
    }

    public function getLatestConversationMessages(string $conversationId, int $limit): Collection
    {
        return collect();
    }
}
