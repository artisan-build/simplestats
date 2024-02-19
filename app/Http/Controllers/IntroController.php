<?php

namespace App\Http\Controllers;

use App\Http\Requests\IntroRequest;
use App\Models\Project;
use App\Providers\RouteServiceProvider;
use Inertia\Inertia;

class IntroController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(IntroRequest $request)
    {
        if ($request->has('team') &&
            $request->has('currency') &&
            $request->has('project') &&
            $request->has('instance')
        ) {
            $request->user()->currentTeam->update([
                'name' => $request->team,
                'currency' => $request->currency,
            ]);

            Project::create([
                'name' => $request->project,
            ])->instances()->create([
                'name' => $request->instance,
            ]);

            return redirect(RouteServiceProvider::HOME)->with('flash', [
                'banner' => 'Team settings saved!',
            ]);
        }

        return Inertia::render('Intro', [
            'team' => $request->user()->currentTeam->name,
        ]);
    }
}
