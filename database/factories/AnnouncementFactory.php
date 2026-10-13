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
            'subject' => fake()->unique()->sentence(4),
            'body' => '# '.fake()->sentence(3)."\n\n".fake()->paragraph(),
            'last_sent_at' => now(),
        ];
    }
}
