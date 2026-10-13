<?php

namespace App\Providers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Cashier;
use Spark\Spark;

class SparkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.app_type') !== 'saas') {
            return;
        }

        Cashier::useTransactionModel(Transaction::class);

        Spark::billable(User::class)->resolve(fn (Request $request) => $request->user());

        Spark::billable(User::class)->authorize(fn (User $billable, Request $request): bool => $request->user() &&
               $request->user()->id == $billable->id);
    }
}
