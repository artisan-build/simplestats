<?php

namespace App\Sentry;

use Filament\Notifications\Collection;
use Sentry\Event;
use Sentry\EventHint;
use TypeError;

class BeforeSend
{
    /**
     * Filter Sentry events before they are sent.
     *
     * Must be referenced as an array callable in config/sentry.php (not a closure)
     * so the config stays serializable for `php artisan config:cache`.
     */
    public static function handle(Event $event, ?EventHint $hint): ?Event
    {
        $exception = $hint?->exception;

        // Malformed/tampered Livewire payloads against the Filament notifications component send
        // the hydrated collection items as ints instead of arrays, which makes Filament's
        // Notifications\Collection::fromLivewire() throw a TypeError. This is the same class of
        // probing as CannotUpdateLockedPropertyException (handled, no users affected), so we drop
        // it rather than report it. The validation gap is in Filament vendor code we don't control.
        if ($exception instanceof TypeError
            && str_contains($exception->getMessage(), Collection::class)) {
            return null;
        }

        return $event;
    }
}
