<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetGroupedBreakdown;
use App\Mcp\Tools\GetKpiStats;
use App\Mcp\Tools\ListStatsCapabilities;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('SimpleStats')]
#[Version('1.0.0')]
#[Instructions('Query the authenticated team\'s SimpleStats analytics: KPI time series (visitors, registrations, revenue, active users) and breakdowns by dimensions like referrer, source, campaign, country, or device. Call list-stats-capabilities first to discover projects, dimensions, presets, and KPI keys. All data is automatically scoped to the current team.')]
class SimpleStatsServer extends Server
{
    protected array $tools = [
        ListStatsCapabilities::class,
        GetKpiStats::class,
        GetGroupedBreakdown::class,
    ];
}
