<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsLoginRequest;
use App\Http\Resources\StatsLoginResource;
use App\Models\StatsLogin;
use App\Services\StatsService;

class StatsLoginController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsLogin::class, 'stats_login');
    }

    public function store(StatsLoginRequest $request): StatsLoginResource
    {
        // on logins, we do not do a updateOrCreate, cause the client not sends ids, which makes not really sense for logins.
        // also only to the second login (to check NAU after reg. on the same day) we save logins at the same day,
        // else we only save one login per day

        if (! StatsLogin::where('counter', StatsService::NAU_LOGIN_COUNTER)->exists()) {
            $statsLogin = StatsLogin::create($request->validated());
        } elseif (! $statsLogin = StatsLogin::whereDate('time', $request->time)->first()) {
            $statsLogin = StatsLogin::create($request->validated());
        }

        return new StatsLoginResource($statsLogin);
    }
}
