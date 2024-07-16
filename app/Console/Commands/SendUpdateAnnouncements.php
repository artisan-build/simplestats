<?php

namespace App\Console\Commands;

use App\Mail\UpdateAnnouncement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendUpdateAnnouncements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-update-announcements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send update announcements to all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach (User::whereNotNull('email_verified_at')->get() as $user) {
            Mail::to($user->email)->send(new UpdateAnnouncement($user));

            // in non-prod environments do only send one test mail
            if (app()->environment() !== 'production') {
                break;
            }
        }

        $this->info('Update announcement emails have been sent successfully.');
    }
}
