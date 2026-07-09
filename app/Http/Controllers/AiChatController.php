<?php

namespace App\Http\Controllers;

use App\Ai\Agents\StatsAssistant;
use App\Enums\StatsRangePreset;
use App\Helpers\AppHelper;
use App\Http\Requests\AiChatRequest;
use App\Services\Ai\AiChatStreamer;
use App\Services\Ai\AiConversationService;
use App\Services\Ai\TeamAiConfig;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AiChatController extends Controller
{
    public function store(AiChatRequest $request, AiConversationService $conversationService, AiChatStreamer $streamer, TeamAiConfig $teamAiConfig): Response
    {
        AppHelper::ensureTeamTimezone();

        $user = $request->user();

        $byokProvider = $teamAiConfig->activate($user->team);

        // Fair-use cap on the app's own AI quota. BYOK teams pay their own
        // tokens, so they are exempt.
        $dailyLimit = (int) config('ai.chat.daily_message_limit');

        if ($byokProvider === null && $dailyLimit > 0
            && ! RateLimiter::attempt('ai-chat-daily:'.AppHelper::getTeamId(), $dailyLimit, fn () => true, 60 * 60 * 24)) {
            return response()->json([
                'message' => 'Your team reached its daily AI message limit. Please try again tomorrow, or add your own AI provider key on the team settings page.',
            ], 429);
        }

        $conversationId = $conversationService->resolveOrCreate(
            $user,
            $request->input('conversation_id'),
            $request->input('message'),
        );

        $agent = new StatsAssistant(
            currentProjectId: $request->integer('project_id') ?: null,
            currentPreset: StatsRangePreset::tryFrom((string) $request->input('context_preset'))?->value,
        );

        $byokProvider = $teamAiConfig->activate($user->team);

        $stream = $agent
            ->continue($conversationId, as: $user)
            ->stream($request->input('message'), provider: $byokProvider);

        return $streamer->response($stream, $conversationId, usesTeamKey: $byokProvider !== null);
    }
}
