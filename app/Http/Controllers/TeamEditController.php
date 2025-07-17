<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamEditController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Team $team)
    {
        Gate::forUser($request->user())->authorize('update', $team);

        return Inertia::render('Teams/Create', [
            'team' => $team,
        ]);
    }
}
