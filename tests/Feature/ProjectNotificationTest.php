<?php

use App\Enums\UserNotificationType;
use App\Models\Project;

it('defaults project alert tracking settings to true for enabled tracking types', function () {
    $usage = Project::USAGE_VISITORS
        | Project::USAGE_USERS
        | Project::USAGE_PAYMENTS;

    $project = new Project(['usage' => $usage]);

    $notifications = $project->getDefaultUserNotificationTypes();
    $alert = $notifications->firstWhere('type', UserNotificationType::PROJECT_ALERT);

    expect($alert->settings['tracking'])
        ->toHaveKey('registrations', true)
        ->toHaveKey('payments', true);
});

it('excludes disabled tracking types from project alert settings', function () {
    $project = new Project(['usage' => Project::USAGE_VISITORS]);

    $notifications = $project->getDefaultUserNotificationTypes();
    $alert = $notifications->firstWhere('type', UserNotificationType::PROJECT_ALERT);

    expect($alert->settings['tracking'])->toBeEmpty();
});
