<?php

namespace App\Models;

use Spark\Billable;
use Spark\Plan;

if (config('app.app_type') === 'default') {
    class User extends UserBase
    {
        use Billable {
            sparkPlan as aliasSparkPlan;
        }

        public function sparkPlan(): Plan
        {
            if (! ($plan = $this->aliasSparkPlan())) {
                $planConfig = $this->getPlanConfig();

                $plan = (new Plan($planConfig['name'], 0))
                    ->interval('monthly')
                    ->incentive($planConfig['monthly_incentive'] ?? '', $planConfig['yearly_incentive'] ?? '')
                    ->shortDescription($planConfig['short_description'])
                    ->features($planConfig['features'])
                    ->options($planConfig['options'] ?? [])
                    ->status(isset($planConfig['archived']) ? ! $planConfig['archived'] : true);
            }

            return $plan;
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
