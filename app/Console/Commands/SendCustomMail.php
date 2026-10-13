<?php

namespace App\Console\Commands;

use App\Mail\CustomMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCustomMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-custom-mail {userId} {title} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send custom mail to a user';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $user = User::findOrFail($this->argument('userId'));

        Mail::to($user->email)->send(new CustomMail($user, $this->argument('title'), $this->argument('message')));

        $this->info('Custom mail has been sent successfully.');
    }
}
