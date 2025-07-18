<?php

namespace App\Http\Controllers;

use App\Actions\Jetstream\UpdateTeam;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SetupTeamController extends Controller
{
    public function create(Request $request)
    {
        return Inertia::render('Setup/Team', [
            'team' => $request->user()->currentTeam,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // the team already exists, so we update it
        $team = app(UpdateTeam::class)->update($user, $user->currentTeam, $request->all());

        // we create a default project for the team
        if ($team->projects()->count() <= 0) {
            $team->projects()->create([
                'name' => '',
            ]);
        }

        return redirect()->route('setup.project.create');
    }
}
