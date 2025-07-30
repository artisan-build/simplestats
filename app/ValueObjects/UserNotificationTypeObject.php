<?php

namespace App\ValueObjects;

use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;

readonly class UserNotificationTypeObject
{
    public function __construct(
        public readonly UserNotificationType $type,
        public readonly ?UserNotificationFrequency $frequency,
        public readonly array $settings,
    ) {
    }
}
