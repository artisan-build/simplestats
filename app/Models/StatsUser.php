<?php

namespace App\Models;

use App\Concerns\HasInstanceScope;
use App\Concerns\HasTeamScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatsUser extends Model
{
    use HasFactory;
    use HasTeamScope;
    use HasInstanceScope;

    public $timestamps = false;

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        // in non-cli env these are always set/overwritten by the auth user (check HasInstanceScope and HasTeamScope)
        'team_id',
        'project_id',
        'instance_id',
        // in non-cli env these are always set/overwritten by the auth user (check HasInstanceScope and HasTeamScope)
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
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
