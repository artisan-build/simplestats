<?php

namespace App\Models;

use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(StatsObserver::class)]
class StatsUser extends BaseStat
{
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'track_source',
        'track_medium',
        'track_campaign',
        'track_term',
        'track_content',
        'time',
    ];

    protected static array $trackingTypes = [
        'track_source' => StatsTrackSource::class,
        'track_medium' => StatsTrackMedium::class,
        'track_campaign' => StatsTrackCampaign::class,
        'track_term' => StatsTrackTerm::class,
        'track_content' => StatsTrackContent::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            foreach (self::$trackingTypes as $property => $class) {
                if (!empty($user->$property)) {
                    $trackSource = ['team_id' => $user->team_id, 'project_id' => $user->project_id, 'name' => $user->$property];
                    $statsProperty = "stats_{$property}_id";
                    $class::unguard();
                    $user->$statsProperty = $class::where($trackSource)->first()?->id ?? $class::create($trackSource)->id;
                }
                unset($user->$property);
            }
        });
    }

    public function statsLogins(): HasMany
    {
        return $this->hasMany(StatsLogin::class);
    }

    public function statsPayments(): HasMany
    {
        return $this->hasMany(StatsPayment::class);
    }
}
