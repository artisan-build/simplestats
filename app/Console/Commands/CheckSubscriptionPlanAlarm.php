<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

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
    public function handle()
    {
        Team::with('owner')->get()->each(function($team) {
            if (empty($team->owner)) return;
            $team->owner->load('subscriptions');
            app(SubscriptionService::class)->checkPlanAlarm($team, true);
        });
    }
}
