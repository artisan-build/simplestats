<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Laravel\Paddle\Cashier;

class SubscriptionService
{
    public function getFrontendPlans(string $billableType = 'user'): Collection
    {
        $plans = collect(config("spark.billables.$billableType")['plans']);
        $planIds = $plans->flatMap(fn($item) => [$item['monthly_id'], $item['yearly_id']])->filter();
        $planPrices = $this->getPlanIdToPrices($planIds);

        return $plans->map(function ($item) use ($planPrices) {
            return [
                'name' => $item['name'],
                'short_description' => $item['short_description'],
                'price_monthly' => $planPrices[$item['monthly_id']]->formatted_unit_totals['total'] ?? '0',
                'price_yearly' => $planPrices[$item['yearly_id']]->formatted_unit_totals['total'] ?? '0',
                'yearly_incentive' => $item['yearly_incentive'] ?? 0,
                'features' => $item['features'],
                'promoted' => $item['promoted'] ?? false,
            ];
        });
    }

    public function getPlanIdToPrices(Collection $planIds): Collection
    {
        $ttl = (app()->isProduction()) ? 86400 : 0 ;

        return Cache::remember('paddle-prices', $ttl, function () use ($planIds) {
            return Cashier::previewPrices($planIds, [
                'customer_ip_address' => request()->ip(),
            ])->mapWithKeys(fn($item) => [$item->price['id'] => $item]);
        });
    }
}
