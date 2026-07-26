<?php

namespace App\Models;

use App\Concerns\HasPublicUuid;
use App\Enums\UserNotificationFrequency;
use App\Enums\UserNotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

class UserNotification extends Model
{
    use HasPublicUuid;

    protected $guarded = false;

    protected $hidden = [
        'id',
        'user_id',
        'notifiable_id',
        'notifiable_type',
    ];

    protected $casts = [
        'frequency' => UserNotificationFrequency::class,
        'type' => UserNotificationType::class,
        'settings' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function updateSettings($key, $value): void
    {
        $settings = $this->settings;
        Arr::set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }
}
