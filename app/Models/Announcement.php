<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    protected $fillable = [
        'subject',
        'body',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
        ];
    }

    /**
     * Persist the latest version of a sent announcement: the loaded record wins,
     * otherwise an identical subject updates its record, otherwise a new one is created.
     */
    public static function recordSend(string $subject, string $body, ?int $loadedId = null): self
    {
        $announcement = ($loadedId ? static::find($loadedId) : null)
            ?? static::where('subject', $subject)->first()
            ?? new static;

        $announcement->fill([
            'subject' => $subject,
            'body' => $body,
            'last_sent_at' => now(),
        ])->save();

        return $announcement;
    }
}
