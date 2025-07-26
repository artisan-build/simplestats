<?php

namespace App\Listeners;

class SetCurrentTeam
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $event->user->current_team_id = $event->team->id;
        $event->user->save();
    }
}
