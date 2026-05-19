<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Laravel\Paddle\Cashier;

class SubscriptionService
{
    const PLAN_FREE = 'Free';

    const PLAN_STARTER = 'Starter';

    const PLAN_PLUS = 'Plus';

    const PLAN_BUSINESS = 'Business';

    const PLAN_ENTERPRISE = 'Enterprise';

    const UNLIMITED_AMOUNT = -1;

    const ALARM_PERCENTAGE = 70;

    public function getFrontendPlans(string $billableType = 'user'): Collection
    {
        $plans = collect(config("spark.billables.$billableType.plans", []));
        $planIds = $plans->flatMap(fn ($item) => [$item['monthly_id'], $item['yearly_id']])->filter();
        $planPrices = $this->getPlanIdToPrices($planIds);

        $discount = $this->getDiscount();

        return $plans->map(function ($item) use ($planPrices, $discount) {
            //            if (isset($planPrices[$item['monthly_id']])) dd($planPrices[$item['yearly_id']]);
            return [
                'name' => $item['name'],
                'short_description' => $item['short_description'],
                'price_monthly' => $planPrices[$item['monthly_id']]->totals['subtotal'] ?? 0,
                'price_yearly' => $planPrices[$item['yearly_id']]->totals['subtotal'] ?? 0,
                'tax_mode_monthly' => $planPrices[$item['monthly_id']]->price['tax_mode'] ?? '',
                'tax_mode_yearly' => $planPrices[$item['yearly_id']]->price['tax_mode'] ?? '',
                'yearly_incentive' => $this->calculateYearlyIncentive(
                    $planPrices[$item['monthly_id']]->totals['subtotal'] ?? 0,
                    $planPrices[$item['yearly_id']]->totals['subtotal'] ?? 0,
                ),
                // do not show/expose FQN to FE...
                'features' => collect($item['features'])->mapWithKeys(fn ($item, $key) => [class_basename($key) => $item]),
                'options' => collect($item['options'])->mapWithKeys(fn ($item, $key) => [class_basename($key) => $item]),
                'promoted' => $item['promoted'] ?? false,
                'discount' => $discount['percent'] ?? 0,
            ];
        });
    }

    public function getPlanIdToPrices(Collection $planIds): Collection
    {
        // sometimes when I work on it, I put the ttl in local mode to 0
        $ttl = (app()->isProduction()) ? 86400 : 86400;

        return Cache::remember('paddle-prices', $ttl, function () use ($planIds) {
            return Cashier::previewPrices($planIds, [
                'customer_ip_address' => request()->ip(),
            ])->mapWithKeys(fn ($item) => [$item->price['id'] => $item]);
        });
    }

    private function calculateYearlyIncentive(int $monthlyPrice, int $yearlyPrice): ?int
    {
        if ($monthlyPrice <= 0 || $yearlyPrice <= 0) {
            return null;
        }

        $annualFromMonthly = $monthlyPrice * 12;
        $savings = (int) round(($annualFromMonthly - $yearlyPrice) / $annualFromMonthly * 100);

        return $savings > 0 ? $savings : null;
    }

    public function getPlanAlarm(Team $team): array
    {
        $teamOwner = $team->owner;
        $teamPlan = $teamOwner->sparkPlan();

        $alarms = [];

        if ($teamPlan->options['Events'] !== self::UNLIMITED_AMOUNT) {
            $currentAmount = $team->events_count_monthly;
            $currentPercent = floor($currentAmount / $teamPlan->options['Events'] * 100);

            // this can only happen if the subscription can't be renewed
            if ($currentPercent > 100) {
                $alarms[0] = [
                    'percent' => $currentPercent,
                    'text' => "Your subscription has been canceled or couldn't be renewed automatically. You're now on the free plan and have reached your monthly events limit.",
                ];
            } elseif ($currentPercent > self::ALARM_PERCENTAGE) {
                $monthlyEventsDescr = strtolower($teamPlan->features['Events']);
                $alarms[] = [
                    'percent' => $currentPercent,
                    'text' => "You've used $currentPercent% of your {$teamPlan->name} plan's {$monthlyEventsDescr}.",
                ];
            }
        }

        return $alarms;
    }

    public function getDiscount(): ?array
    {
        if (app()->environment('demo')) {
            return null;
        }

        $discount = config('spark.discount');

        if (now()->gte(Carbon::parse($discount['duration']['start'])->startOfDay()) && now()->lte(Carbon::parse($discount['duration']['end'])->endOfDay())) {
            return $discount;
        }

        return null;
    }
}
