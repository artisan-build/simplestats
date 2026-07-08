<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Models\Conversation;

class AiConversationController extends Controller
{
    public function show(Request $request, Conversation $conversation, AiConversationService $conversationService): JsonResponse
    {
        Gate::allowIf($conversation->user_id === $request->user()->id);

        return response()->json($conversationService->toPayload($conversation));
    }
}
