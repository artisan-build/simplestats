<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsUserRequest;
use App\Http\Resources\StatsUserResource;
use App\Models\StatsUser;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Http\JsonResponse;

class StatsUserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsUser::class, 'stats_user');
    }

    public function store(StatsUserRequest $request): JsonResponse|StatsUserResource
    {
        if (Browser::parse(urldecode($request->user_agent))->isBot()) {
            // we respond, cause else the client would retry these requests again and again if they use queues...
            return response()->json(['message' => 'Bots are not allowed'], 200);
        }

        $statsUser = StatsUser::updateOrCreate(['id' => $request->id], $request->validated());

        return new StatsUserResource($statsUser);
    }
}
