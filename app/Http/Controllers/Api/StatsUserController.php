<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsUserRequest;
use App\Http\Resources\StatsUserResource;
use App\Models\StatsUser;

class StatsUserController extends Controller
{
//    public function index(Request $request): StatsUserCollection
//    {
//        $statsUsers = StatsUser::all();
//
//        return new StatsUserCollection($statsUsers);
//    }

    public function store(StatsUserRequest $request): StatsUserResource
    {
        $statsUser = StatsUser::create($request->validated());

        return new StatsUserResource($statsUser);
    }

//    public function show(Request $request, StatsUser $statsUser): StatsUserResource
//    {
//        return new StatsUserResource($statsUser);
//    }
//
//    public function update(StatsUserRequest $request, StatsUser $statsUser): StatsUserResource
//    {
//        $statsUser->update($request->validated());
//
//        return new StatsUserResource($statsUser);
//    }
//
//    public function destroy(Request $request, StatsUser $statsUser): Response
//    {
//        $statsUser->delete();
//
//        return response()->noContent();
//    }
}
