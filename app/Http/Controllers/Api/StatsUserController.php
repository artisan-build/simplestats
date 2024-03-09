<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsUserRequest;
use App\Http\Resources\StatsUserResource;
use App\Models\StatsUser;

class StatsUserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StatsUser::class, 'stats_user');
    }

    public function store(StatsUserRequest $request): StatsUserResource
    {
        $statsUser = StatsUser::updateOrCreate(['id' => $request->id], $request->validated());

        return new StatsUserResource($statsUser);
    }
}
