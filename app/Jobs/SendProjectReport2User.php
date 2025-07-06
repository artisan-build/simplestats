<?php

namespace App\Jobs;

use App\Models\UserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendProjectReport2User implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserNotification $userNotification
    ) {
        $this->onQueue('low');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->userNotification->loadMissing(['user', 'notifiable']);

        // TODO build report and send it to user
    }
}
