<?php

namespace App\Http\Middleware;

use App\Models\Campaign;
use App\Models\Project;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permanently redirects pre-uuid bookmarks like /projects/5 to the uuid url.
 * Must run before SubstituteBindings, where the numeric id would 404. Lookups
 * are team-scoped (HasTeamScope, explicit membership check for teams), so
 * foreign ids still 404 and leak nothing.
 */
class RedirectLegacyIdUrls
{
    private const array PARAMS = [
        'project' => Project::class,
        'team' => Team::class,
        'campaign' => Campaign::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if ($route === null || $route->getName() === null || ! $request->isMethod('GET') || $request->user() === null) {
            return $next($request);
        }

        foreach (self::PARAMS as $param => $modelClass) {
            $value = $route->parameter($param);

            if (! is_string($value) || ! ctype_digit($value)) {
                continue;
            }

            $model = $modelClass::find((int) $value);

            if ($model instanceof Team && ! $request->user()->belongsToTeam($model)) {
                $model = null;
            }

            if ($model !== null) {
                return to_route($route->getName(), [...$route->parameters(), $param => $model, ...$request->query()], 301);
            }
        }

        return $next($request);
    }
}
