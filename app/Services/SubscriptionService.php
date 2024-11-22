<?php

namespace App\Services;

use App\Mail\SubscriptionPlanAlarm;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Number;
use Laravel\Paddle\Cashier;

class SubscriptionService
{
    const PLAN_FREE = 'Free';
    const PLAN_PLUS = 'Plus';
    const PLAN_BUSINESS = 'Business';
    const PLAN_ENTERPRISE = 'Enterprise';

    const UNLIMITED_AMOUNT = -1;
    const ALARM_PERCENTAGE = 70;

    public function getFrontendPlans(string $billableType = 'user'): Collection
    {
        $plans = collect(config("spark.billables.$billableType")['plans']);
        $planIds = $plans->flatMap(fn($item) => [$item['monthly_id'], $item['yearly_id']])->filter();
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
                'yearly_incentive' => $item['yearly_incentive'] ?? 0,
                // do not show/expose FQN to FE...
                'features' => collect($item['features'])->mapWithKeys(fn($item, $key) => [class_basename($key) => $item]),
                'options' => collect($item['options'])->mapWithKeys(fn($item, $key) => [class_basename($key) => $item]),
                'promoted' => $item['promoted'] ?? false,
                'discount' => $discount['percent'] ?? 0,
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

    public function checkPlanAlarm(Team $team, bool $sendMail = false): array
    {
        $teamOwner = $team->owner;
        $teamPlan = $teamOwner->sparkPlan();

        $alarms = [];
        foreach ($teamPlan->options as $featureType => $featureAmount) {
            $featureTypeBase = class_basename($featureType);
            if (! str($featureTypeBase)->startsWith('Stats') || $featureAmount === self::UNLIMITED_AMOUNT) {
                continue;
            }

            /** @var $featureType Model */
            // as this is also called in the cronjob (here we have no team), we need to apply the team_id manually
            $currentAmount = $featureType::where('team_id', $team->id)->withoutGlobalScope('instance')->count();
            $currentPercent = round($currentAmount / $featureAmount * 100, 0);

            // this can only happen if the subscription can't be renewed
            if ($currentPercent >= 100) {
                $alarms[0] = [
                    'percent' => $currentPercent,
                    'text' => "It seems like your subscription is canceled or could not be renewed automatically! You're now back on the free plan and reached the tracking limit. We will stop tracking and storing your data!"
                ];

                break;
            }

            if ($currentPercent > self::ALARM_PERCENTAGE) {
                $alarms[] = [
                    'percent' => $currentPercent,
                    'text' => "You reached $currentPercent% of the {$teamPlan->features[$featureType]} tracking limit of your {$teamPlan->name} plan.",
                ];
            }
        }

        if (! empty($alarms) && $sendMail) {
            Mail::to($teamOwner->email)->send(new SubscriptionPlanAlarm($teamOwner, $alarms));
        }

        return $alarms;
    }

    public function getDiscount(): ?array
    {
        $discount = config('spark.discount');

        if (now()->gte(Carbon::parse($discount['duration']['start'])->startOfDay()) && now()->lte(Carbon::parse($discount['duration']['end'])->endOfDay())) {
            return $discount;
        }

        return null;
    }
}
