<?php

namespace App\ValueObjects;

use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;

readonly class UserNotificationTypeObject
{
    public function __construct(
        public UserNotificationType $type,
        public ?UserNotificationFrequency $frequency,
        public array $settings,
    ) {
    }
}
