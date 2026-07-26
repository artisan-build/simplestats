<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\Team;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Jetstream\Jetstream;

class CurrentTeamController extends Controller
{
    public function update(Request $request)
    {
        $team = Jetstream::newTeamModel()->findOrFail(
            AppHelper::resolvePublicId(Team::class, $request->team_id)
        );

        if (! $request->user()->switchTeam($team)) {
            abort(403);
        }

        // Redirect back to the previous page but without query string, so
        // team-scoped stats filters (project_id, ...) from the old team don't
        // leak into the new team. The new team's persisted filters are loaded
        // from DB on the next dashboard render.
        $referer = $request->headers->get('referer') ?: url()->previous();
        $path = parse_url($referer, PHP_URL_PATH) ?: '/';

        if (preg_match('#^/teams/[^/]+(.*)#', $path, $matches)) {
            $path = '/teams/'.$team->uuid.$matches[1];
        } elseif (str_starts_with($path, '/setup')) {
            $path = RouteServiceProvider::HOME;
        } elseif (preg_match('#^(/[^/]+)/(\d+|[0-9a-fA-F-]{36})(/|$)#', $path, $matches)) {
            // Strips a numeric or uuid id segment, but leaves non-id segments
            // like /dashboard/metric/{kpi} untouched.
            $path = $matches[1];
        }

        return redirect($path)->banner('The team has been switched.');
    }
}
