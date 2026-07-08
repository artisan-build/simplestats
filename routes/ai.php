<?php

use App\Mcp\Servers\SimpleStatsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('simplestats', SimpleStatsServer::class);
