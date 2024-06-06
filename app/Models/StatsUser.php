<?php

namespace App\Models;

use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stevebauman\Location\Facades\Location;

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
        'ip',
        'user_agent',
        'track_country',
        'track_source',
        'track_medium',
        'track_campaign',
        'track_term',
        'track_content',
        'time',
    ];

    protected static array $trackingTypes = [
        'track_country' => StatsTrackCountry::class,
        'track_source' => StatsTrackSource::class,
        'track_medium' => StatsTrackMedium::class,
        'track_campaign' => StatsTrackCampaign::class,
        'track_term' => StatsTrackTerm::class,
        'track_content' => StatsTrackContent::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($statsUser) {
            dump($statsUser);
            foreach (self::$trackingTypes as $property => $class) {
                if (!empty($statsUser->$property)) {
                    $trackSource = ['team_id' => $statsUser->team_id, 'project_id' => $statsUser->project_id, 'name' => $statsUser->$property];
                    $statsProperty = "stats_{$property}_id";
                    $class::unguard();
                    $statsUser->$statsProperty = $class::where($trackSource)->first()?->id ?? $class::create($trackSource)->id;
                }
                unset($statsUser->$property);
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

    public function ip(): Attribute
    {
        return Attribute::make(
            set: function(string $value) {
                $this->track_country = Location::get($value)->countryName;
                dump($this->track_country, $value);
                return $value;
            },
        );
    }
}
