<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Models\Conversation;

class AiConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->conversations()
                ->latest('updated_at')
                ->limit(20)
                ->get(['id', 'title', 'updated_at'])
        );
    }

    public function show(Request $request, Conversation $conversation, AiConversationService $conversationService): JsonResponse
    {
        Gate::allowIf($conversation->user_id === $request->user()->id);

        return response()->json($conversationService->toPayload($conversation));
    }
}
