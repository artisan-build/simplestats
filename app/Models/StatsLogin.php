<?php

namespace App\Models;

use App\Concerns\HasCounterByStatsUser;
use App\Concerns\HasStatsUserAssurance;
use App\Observers\StatsObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(StatsObserver::class)]
class StatsLogin extends BaseStat
{
    use HasCounterByStatsUser;
    use HasStatsUserAssurance;

    /**
     * @return BelongsTo<StatsUser, $this>
     */
    public function statsUser(): BelongsTo
    {
        return $this->belongsTo(StatsUser::class);
    }

    protected function ip(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): array {
                if ($this->statsUser && ! $this->statsUser->stats_location_country_id) {
                    $this->statsUser->ip = $value;
                    $this->statsUser->save();
                }

                return [];
            },
        );
    }

    protected function userAgent(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): array {
                if ($this->statsUser && ! $this->statsUser->stats_device_type_id) {
                    $this->statsUser->user_agent = $value;
                    $this->statsUser->save();
                }

                return [];
            },
        );
    }
}
