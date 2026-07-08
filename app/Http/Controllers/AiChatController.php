<?php

namespace App\Http\Controllers;

use App\Ai\Agents\StatsAssistant;
use App\Helpers\AppHelper;
use App\Http\Requests\AiChatRequest;
use App\Services\Ai\AiChatStreamer;
use App\Services\Ai\AiConversationService;
use App\Services\Ai\TeamAiConfig;
use Symfony\Component\HttpFoundation\Response;

class AiChatController extends Controller
{
    public function store(AiChatRequest $request, AiConversationService $conversationService, AiChatStreamer $streamer, TeamAiConfig $teamAiConfig): Response
    {
        AppHelper::ensureTeamTimezone();

        $user = $request->user();

        $conversationId = $conversationService->resolveOrCreate(
            $user,
            $request->input('conversation_id'),
            $request->input('message'),
        );

        $agent = new StatsAssistant(currentProjectId: $request->integer('project_id') ?: null);

        $byokProvider = $teamAiConfig->activate($user->team);

        $stream = $agent
            ->continue($conversationId, as: $user)
            ->stream($request->input('message'), provider: $byokProvider);

        return $streamer->response($stream, $conversationId, usesTeamKey: $byokProvider !== null);
    }
}
