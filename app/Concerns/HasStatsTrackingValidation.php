<?php

namespace App\Concerns;

trait HasStatsTrackingValidation
{
    protected array $trackingValidation = [
        'ip' => ['nullable', 'ip'],
        'user_agent' => ['nullable', 'string'],
        'location_country' => ['nullable', 'string'],
        'track_referer' => ['nullable', 'string'],
        'track_source' => ['nullable', 'string'],
        'track_medium' => ['nullable', 'string'],
        'track_campaign' => ['nullable', 'string'],
        'track_term' => ['nullable', 'string'],
        'track_content' => ['nullable', 'string'],
        'page_entry' => ['nullable', 'string'],
    ];
}
