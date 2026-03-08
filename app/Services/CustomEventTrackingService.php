<?php

namespace App\Services;

use Illuminate\Support\Str;
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;
use SimpleStatsIo\LaravelClient\Visitor;

class CustomEventTrackingService
{
    public function trackGetStartedClick(): void
    {
        $visitorHash = session('simplestats.visitor_hash');

        if ($visitorHash) {
            SimplestatsClient::trackCustomEvent(
                id: 'get-started-click-'.Str::uuid(),
                name: 'Get Started Clicked',
                value: null,
                user: new Visitor($visitorHash),
            );
        }
    }
}
