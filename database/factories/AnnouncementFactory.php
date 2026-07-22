<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => $this->faker->unique()->sentence(4),
            'body' => "# {$this->faker->sentence(3)}\n\n{$this->faker->paragraph()}",
            'last_sent_at' => now(),
        ];
    }
}
