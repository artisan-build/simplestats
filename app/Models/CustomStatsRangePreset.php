<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use App\Enums\StatsRangeType;
use Database\Factories\CustomStatsRangePresetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomStatsRangePreset extends Model
{
    /** @use HasFactory<CustomStatsRangePresetFactory> */
    use HasFactory;

    use HasPublicUuid;

    protected $fillable = [
        'name',
        'range_amount',
        'range_type',
        'sort_order',
    ];

    protected $hidden = [
        'id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'range_type' => StatsRangeType::class,
            'range_amount' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{id: string, name: string, sortWeight: int}
     */
    public function toOption(): array
    {
        return [
            'id' => 'custom_'.$this->id,
            'name' => $this->name,
            'sortWeight' => $this->range_type->toSortWeight($this->range_amount),
        ];
    }

    /**
     * @return array{range_start: string, range_amount: int, range_type: string}
     */
    public function toRange(): array
    {
        return [
            'range_start' => now()->format(StatsRangeType::DATE_FORMAT),
            'range_amount' => $this->range_amount,
            'range_type' => $this->range_type->value,
        ];
    }
}
