<?php

use App\Http\Middleware\Mcp\EnsureMcpProjectToken;
use App\Http\Middleware\SetTeamContext;
use App\Mcp\Servers\SimpleStatsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('simplestats', SimpleStatsServer::class);

// routes/ai.php carries no middleware group, so everything is attached
// explicitly. Auth = project API token as bearer (same credential as the
// REST API); the transport is stateless, the token travels with every POST.
Mcp::web('mcp', SimpleStatsServer::class)
    ->middleware([
        'auth:sanctum',
        EnsureMcpProjectToken::class,
        SetTeamContext::class,
        'throttle:mcp',
    ])
    ->name('mcp.web');
