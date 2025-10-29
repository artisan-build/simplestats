<?php

namespace App\Models;

use Carbon\CarbonInterface;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePersonWithCondition;

if (config('app.app_type') === 'default') {
    class User extends UserBase implements TrackablePersonWithCondition
    {
        // do not simplify FQN here, cause spark does not exist in self-hosted...
        use \Spark\Billable {
            sparkPlan as aliasSparkPlan;
        }

        public function sparkPlan(): \Spark\Plan
        {
            if (! ($plan = $this->aliasSparkPlan())) {
                $planConfig = $this->getPlanConfig();

                $plan = (new \Spark\Plan($planConfig['name'], 0))
                    ->interval('monthly')
                    ->incentive($planConfig['monthly_incentive'] ?? '', $planConfig['yearly_incentive'] ?? '')
                    ->shortDescription($planConfig['short_description'])
                    ->features($planConfig['features'])
                    ->options($planConfig['options'] ?? [])
                    ->status(isset($planConfig['archived']) ? ! $planConfig['archived'] : true);
            }

            return $plan;
        }

        public function passTrackingCondition(): bool
        {
            return $this->email_verified_at != null;
        }

        public function watchTrackingFields(): array
        {
            return ['email_verified_at'];
        }

        public function getTrackingTime(): CarbonInterface
        {
            return $this->{self::CREATED_AT};
        }
    }
} else {
    class User extends UserBase
    {
        public function sparkPlan(): object
        {
            $planConfig = $this->getPlanConfig();

            $plan = [
                'name' => $planConfig['name'],
                'features' => $planConfig['features'],
                'options' => $planConfig['options'],
            ];

            return (object) $plan;
        }
    }
}
