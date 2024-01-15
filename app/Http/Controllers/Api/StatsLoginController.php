<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsLoginRequest;
use App\Http\Requests\StatsLoginUpdateRequest;
use App\Http\Resources\StatsLoginResource;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class StatsLoginController extends Controller
{
//    public function index(Request $request): StatsLoginCollection
//    {
//        $statsLogins = StatsLogin::all();
//
//        return new StatsLoginCollection($statsLogins);
//    }

    public function store(StatsLoginRequest $request): StatsLoginResource
    {
        $statsLogin = StatsLogin::create($request->validated());

        return new StatsLoginResource($statsLogin);
    }

//    public function show(Request $request, StatsLogin $statsLogin): StatsLoginResource
//    {
//        return new StatsLoginResource($statsLogin);
//    }
//
//    public function update(StatsLoginUpdateRequest $request, StatsLogin $statsLogin): StatsLoginResource
//    {
//        $statsLogin->update($request->validated());
//
//        return new StatsLoginResource($statsLogin);
//    }
//
//    public function destroy(Request $request, StatsLogin $statsLogin): Response
//    {
//        $statsLogin->delete();
//
//        return response()->noContent();
//    }
}
