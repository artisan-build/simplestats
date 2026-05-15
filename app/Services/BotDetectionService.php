<?php

namespace App\Services;

use DeviceDetector\DeviceDetector;

class BotDetectionService
{
    public static function isBot(?string $userAgent): bool
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return true;
        }

        $detector = new DeviceDetector($userAgent);
        $detector->discardBotInformation();
        $detector->parse();

        if ($detector->isBot()) {
            return true;
        }

        // Real visitors come from browsers or mobile apps. Treat HTTP libraries
        // (curl, python-requests, Go-http-client, ...), feed readers, media
        // players, and PIM clients (email apps) as bot-like, as they almost
        // always represent automated/scripted traffic in a web analytics context.
        return ! $detector->isBrowser() && ! $detector->isMobileApp();
    }
}
