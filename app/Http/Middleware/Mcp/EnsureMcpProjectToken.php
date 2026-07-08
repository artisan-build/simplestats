<?php

namespace App\Http\Middleware\Mcp;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpProjectToken
{
    /**
     * The MCP endpoint only accepts project API tokens (the same credential
     * customers already use for the REST API). The tools then default their
     * project scope to the token's project via this context value.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->user();

        if (! $project instanceof Project) {
            return response()->json(['message' => 'The MCP endpoint requires a project API token.'], 403);
        }

        Context::add('project_id', $project->id);

        return $next($request);
    }
}
