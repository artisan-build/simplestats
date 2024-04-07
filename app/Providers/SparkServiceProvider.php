<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Paddle\Cashier;
use Spark\Plan;
use Spark\Spark;

class SparkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useTransactionModel(Transaction::class);

        Spark::billable(User::class)->resolve(function (Request $request) {
            return $request->user();
        });

        Spark::billable(User::class)->authorize(function (User $billable, Request $request) {
            return $request->user() &&
                   $request->user()->id == $billable->id;
        });

        Spark::billable(User::class)->checkPlanEligibility(function (User $billable, Plan $plan) {
            $messages = [];
            foreach ($plan->options as $planModel => $planCount) {
                // skip for instance data import or unlimited plans
                if (! is_int($planCount) || $planCount === SubscriptionService::UNLIMITED_AMOUNT) {
                    continue;
                }

                // if the current amount/count of data the user already has
                // is higher than the user wants to upgrade/downgrade to, give an error...
                $currentCount = $planModel::count();
                if ($currentCount > $planCount) {
                    $messages[] = "The new plan allows \"{$plan->features[$planModel]}\", you already have $currentCount!";
                }
            }

            if (! empty($messages)) {
                throw ValidationException::withMessages($messages);
            }
        });
    }
}
