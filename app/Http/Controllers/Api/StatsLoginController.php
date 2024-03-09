<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsLoginRequest;
use App\Http\Resources\StatsLoginResource;
use App\Models\StatsLogin;

class StatsLoginController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsLogin::class, 'stats_login');
    }

    public function store(StatsLoginRequest $request): StatsLoginResource
    {
        // on logins, we do not do a updateOrCreate, cause the client not sends ids, which makes not really sense for logins.
        $statsLogin = StatsLogin::create($request->validated());

        return new StatsLoginResource($statsLogin);
    }
}
