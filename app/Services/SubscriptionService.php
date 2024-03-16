<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Laravel\Paddle\Cashier;

class SubscriptionService
{
    const UNLIMITED_AMOUNT = -1;

    public function getFrontendPlans(string $billableType = 'user'): Collection
    {
        $plans = collect(config("spark.billables.$billableType")['plans']);
        $planIds = $plans->flatMap(fn($item) => [$item['monthly_id'], $item['yearly_id']])->filter();
        $planPrices = $this->getPlanIdToPrices($planIds);

        return $plans->map(function ($item) use ($planPrices) {
//            if (isset($planPrices[$item['monthly_id']])) dd($planPrices[$item['yearly_id']]);
            return [
                'name' => $item['name'],
                'short_description' => $item['short_description'],
                'price_monthly' => $planPrices[$item['monthly_id']]->formatted_unit_totals['total'] ?? '$0',
                'price_yearly' => $planPrices[$item['yearly_id']]->formatted_unit_totals['total'] ?? '$0',
                'tax_mode_monthly' => $planPrices[$item['monthly_id']]->price['tax_mode'] ?? '',
                'tax_mode_yearly' => $planPrices[$item['yearly_id']]->price['tax_mode'] ?? '',
                'yearly_incentive' => $item['yearly_incentive'] ?? 0,
                // do not show/expose FQN to FE...
                'features' => collect($item['features'])->mapWithKeys(fn($item, $key) => [class_basename($key) => $item]),
                'options' => collect($item['options'])->mapWithKeys(fn($item, $key) => [class_basename($key) => $item]),
                'promoted' => $item['promoted'] ?? false,
            ];
        });
    }

    public function getPlanIdToPrices(Collection $planIds): Collection
    {
        // sometimes when I work on it, I put the ttl in local mode to 0
        $ttl = (app()->isProduction()) ? 86400 : 86400 ;

        return Cache::remember('paddle-prices', $ttl, function () use ($planIds) {
            return Cashier::previewPrices($planIds, [
                'customer_ip_address' => request()->ip(),
            ])->mapWithKeys(fn($item) => [$item->price['id'] => $item]);
        });
    }
}
