<?php

namespace App\Models;

use App\Enums\StatsRangeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomStatsRangePreset extends Model
{
    /** @use HasFactory<\Database\Factories\CustomStatsRangePresetFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'range_amount',
        'range_type',
        'sort_order',
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
     * @return array{rangeStart: string, rangeAmount: int, rangeType: string}
     */
    public function toRange(): array
    {
        return [
            'rangeStart' => now()->format(StatsRangeType::DATE_FORMAT),
            'rangeAmount' => $this->range_amount,
            'rangeType' => $this->range_type->value,
        ];
    }
}
