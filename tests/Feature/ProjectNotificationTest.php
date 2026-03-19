<?php

use App\Enums\UserNotificationType;
use App\Models\Project;
use App\Services\StatsService;

it('defaults project alert tracking settings to true for enabled tracking types', function () {
    $usage = StatsService::TRACKING_USAGE_BITS_VISITORS
        | StatsService::TRACKING_USAGE_BITS_USERS
        | StatsService::TRACKING_USAGE_BITS_PAYMENTS;

    $project = new Project(['usage' => $usage]);

    $notifications = $project->getDefaultUserNotificationTypes();
    $alert = $notifications->firstWhere('type', UserNotificationType::PROJECT_ALERT);

    expect($alert->settings['tracking'])
        ->toHaveKey('registrations', true)
        ->toHaveKey('payments', true);
});

it('excludes disabled tracking types from project alert settings', function () {
    $project = new Project(['usage' => StatsService::TRACKING_USAGE_BITS_VISITORS]);

    $notifications = $project->getDefaultUserNotificationTypes();
    $alert = $notifications->firstWhere('type', UserNotificationType::PROJECT_ALERT);

    expect($alert->settings['tracking'])->toBeEmpty();
});
