<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatsVisitorRequest;
use App\Http\Resources\StatsVisitorResource;
use App\Models\StatsUser;
use App\Models\StatsVisitor;

class StatsVisitorController extends Controller
{
    /**
     * as long as you are allowed to track StatsUser,
     * you are allowed to track StatsVisitors
     */
    public function __construct()
    {
        $this->authorizeResource(StatsUser::class, 'stats_user');
    }

    public function store(StatsVisitorRequest $request): StatsVisitorResource
    {
        $statsVisitor = StatsVisitor::firstOrCreate([
            'visitor_hash' => StatsVisitor::createHash($request->time, $request->ip, $request->user_agent)
        ], $request->validated());

        return new StatsVisitorResource($statsVisitor);
    }
}
