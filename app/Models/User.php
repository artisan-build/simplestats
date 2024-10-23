<?php

namespace App\Models;

use App\Enums\StatsRangeType;
use App\Jobs\QueuedVerifyEmailJob;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use JoelButcher\Socialstream\HasConnectedAccounts;
use JoelButcher\Socialstream\SetsProfilePhotoFromUrl;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;
use SimpleStatsIo\LaravelClient\Contracts\TrackableUser;
use SimpleStatsIo\LaravelClient\Contracts\TrackableUserWithCondition;
use Spark\Billable;
use Spark\Plan;

class User extends Authenticatable implements TrackableUserWithCondition, MustVerifyEmail
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasFactory;
    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use HasTeams;
    use Notifiable;
    use SetsProfilePhotoFromUrl;
    use TwoFactorAuthenticatable;
    use Billable {
        sparkPlan as aliasSparkPlan;
    }
    use Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'settings',
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
        'settings' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the URL to the user's profile photo.
     */
    public function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn () => $this->profile_photo_path)
            : $this->getPhotoUrl();
    }

    public function sendEmailVerificationNotification(): void
    {
        //dispactches the job to the queue passing it this User object
        QueuedVerifyEmailJob::dispatch($this);
    }

    public function passTrackingCondition(): bool
    {
        return $this->email_verified_at != NULL;
    }

    public function watchTrackingFields(): array
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
            $planConfig = (! app()->isProduction() || in_array($this->id, config('app.plan_auto_max_users'))) ? $plans[count($plans)-1] : $plans[0];

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

    // Compatibility for auth()->team() checks in request/validator classes,
    // Which may be performed by the auth instance (api) or the auth user (normal request)
    public function team(): BelongsTo
    {
        if (is_null($this->current_team_id) && $this->id) {
            $this->switchTeam($this->personalTeam());
        }

        return $this->belongsTo(Jetstream::teamModel(), 'current_team_id');
    }

    public function canImpersonate()
    {
        return $this->isAdmin();
    }

    public function isAdmin(): bool
    {
        return $this->is_admin == 1;
    }

    public function updateSettings($key, $value): void
    {
        $settings = $this->settings;
        Arr::set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    public function makeAdmin()
    {
        $this->is_admin = 1;
        $this->save();
    }
}
