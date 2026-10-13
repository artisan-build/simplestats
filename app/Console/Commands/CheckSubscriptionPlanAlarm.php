<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionPlanAlarm;
use App\Models\Team;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSubscriptionPlanAlarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-subscription-plan-alarm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Checks all teams and sends warning mail if the plan is about to reach it's limit.";

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Team::with('owner', 'billingOwner.subscriptions')->get()->each(function (Team $team): void {
            if (empty($team->owner)) {
                return;
            }

            if ($alarms = resolve(SubscriptionService::class)->getPlanAlarm($team)) {
                Mail::to($team->owner->email)->send(new SubscriptionPlanAlarm($team->owner, $alarms));
                $this->info('Alarm Mail sent to '.$team->owner->email);
            }
        });
    }
}
