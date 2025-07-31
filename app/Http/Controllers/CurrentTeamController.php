<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Jetstream\Jetstream;

class CurrentTeamController extends Controller
{
    public function update(Request $request)
    {
        $team = Jetstream::newTeamModel()->findOrFail($request->team_id);

        if (! $request->user()->switchTeam($team)) {
            abort(403);
        }

        return redirect()->back()->banner('The team has been switched.');
    }
}
