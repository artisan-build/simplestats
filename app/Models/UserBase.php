<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use App\Jobs\QueuedVerifyEmailJob;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use JoelButcher\Socialstream\HasConnectedAccounts;
use JoelButcher\Socialstream\SetsProfilePhotoFromUrl;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Ai\Concerns\HasConversations;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Jetstream\Jetstream;
use Laravel\Sanctum\HasApiTokens;
use Spatie\DeletedModels\Models\Concerns\KeepsDeletedModels;

abstract class UserBase extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasConnectedAccounts;
    use HasConversations;
    use HasFactory;
    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use HasPublicUuid;
    use HasTeams;
    use Impersonate;

    // SoftDeletes and KeepsDeletedModels both declare restore()/restoreQuietly();
    // let SoftDeletes win (un-trash) and keep the archive-restore under an alias.
    use KeepsDeletedModels, SoftDeletes {
        SoftDeletes::restore insteadof KeepsDeletedModels;
        SoftDeletes::restoreQuietly insteadof KeepsDeletedModels;
        KeepsDeletedModels::restore as restoreFromArchive;
        KeepsDeletedModels::restoreQuietly as restoreQuietlyFromArchive;
    }
    use Notifiable;
    use SetsProfilePhotoFromUrl;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        // Internal state that must not leak when users are serialized as team
        // members/owners; the curated auth.user share exposes what the frontend
        // needs (see App\Http\Middleware\ShareInertiaData).
        'settings',
        'is_admin',
        'current_team_id',
        'email_verified_at',
        'last_login_at',
        'previous_login_at',
        'login_summary_pending',
        'inactive_since',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'previous_login_at' => 'immutable_datetime',
        'login_summary_pending' => 'boolean',
        'settings' => 'array',
    ];

    // Archive into deleted_models only on the final forceDelete (the purge job),
    // not on the soft delete that opens the grace window.
    public function shouldKeep(): bool
    {
        return $this->isForceDeleting();
    }

    const SESSION_SETTINGS_KEY = 'temp_user_settings_';

    private function useSessionSettings(): bool
    {
        return app()->environment('demo') || $this->isImpersonated();
    }

    /**
     * Get the user's settings (from session in demo mode or when impersonating, otherwise from database).
     */
    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $dbSettings = json_decode($value, true) ?? [];

                if ($this->useSessionSettings()) {
                    return session()->get(self::SESSION_SETTINGS_KEY.$this->id, $dbSettings);
                }

                return $dbSettings;
            }
        );
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    protected function getPlanConfig()
    {
        $plans = config('spark.billables.user.plans', []);

        if (empty($plans)) {
            return [];
        }

        // local every user gets the highest plan by default :D
        return (! app()->isProduction() || in_array($this->id, config('app.plan_auto_max_users'))) ? $plans[count($plans) - 1] : $plans[0];
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn () => $this->profile_photo_path)
            : $this->getPhotoUrl();
    }

    public function hasVerifiedEmail(): bool
    {
        if (app()->isLocal()) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    public function sendEmailVerificationNotification(): void
    {
        // dispactches the job to the queue passing it this User object
        QueuedVerifyEmailJob::dispatch($this);
    }

    // Compatibility for auth()->team() checks in request/validator classes,
    // Which may be performed by the auth project (api) or the auth user (normal request)
    public function team(): BelongsTo
    {
        if (is_null($this->current_team_id) && $this->id) {
            $fallback = $this->personalTeam() ?? $this->allTeams()->first();
            if ($fallback) {
                $this->switchTeam($fallback);
            }
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

    public function canAccessPanel($panel): bool
    {
        return $this->isAdmin();
    }

    public function updateSettings($key, $value): void
    {
        $settings = $this->settings;
        Arr::set($settings, $key, $value);

        if ($this->useSessionSettings()) {
            session()->put(self::SESSION_SETTINGS_KEY.$this->id, $settings);
        } else {
            $this->update(['settings' => $settings]);
        }
    }

    public function makeAdmin()
    {
        $this->is_admin = 1;
        $this->save();
    }

    public function customStatsRangePresets(): HasMany
    {
        return $this->hasMany(CustomStatsRangePreset::class)->orderBy('sort_order');
    }
}
