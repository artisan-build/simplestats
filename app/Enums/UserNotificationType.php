<?php

namespace App\Enums;

use LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums;

enum UserNotificationType: string
{
    use UsefulEnums;

    case PROJECT_ALERT = 'project_alert';
    case PROJECT_REPORT = 'project_report';

    case DEFAULT = 'default';

    public function title(): string
    {
        return match ($this) {
            self::PROJECT_ALERT => 'Project Alerts',
            self::PROJECT_REPORT => 'Project Reports',
            self::DEFAULT => 'Notifications',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PROJECT_ALERT => 'Receive immediate notifications about key project events, such as new registrations or payments. Project Alerts are turned off by default and can be enabled at any time.',
            self::PROJECT_REPORT => "Scheduled summaries with key metrics like visitors, registrations, user activity, payments, and conversion rates. Choose which stats to include and how often you'd like to receive the report – or send a report manually anytime.",
            self::DEFAULT => 'Default notification type.',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())->mapWithKeys(function (UserNotificationType $type) {
            return [
                $type->value => [
                    'title' => $type->title(),
                    'description' => $type->description(),
                ],
            ];
        })->toArray();
    }
}
