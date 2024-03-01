<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use SimpleStatsIo\LaravelClient\Contracts\TrackableUser;
use SimpleStatsIo\LaravelClient\Contracts\TrackableUserWithCondition;
use Spark\Billable;
use Spark\Plan;

class User extends Authenticatable implements TrackableUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Billable {
        sparkPlan as aliasSparkPlan;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function passTrackingCondition(): bool
    {
        return $this->email_verified_at != NULL;
    }

    public function getTrackingConditionFields(): array
    {
        return ['email_verified_at'];
    }

    public function getTrackingTime(): CarbonInterface
    {
        return $this->{self::CREATED_AT};
    }

    public function sparkPlan(): Plan
    {
        if (!($plan = $this->aliasSparkPlan())) {
            // Fallback to "Free" plan
            $plans = config('spark.billables.user.plans');
            // local every user gets the highest plan by default :D
            $planConfig = (app()->isLocal() && config('app.plan_auto_max')) ? end($plans) : current($plans);

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
