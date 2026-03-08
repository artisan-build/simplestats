<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogPost>
 */
class BlogPostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(rand(4, 8));

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->randomNumber(4),
            'excerpt' => fake()->paragraph(2),
            'body' => $this->generateMarkdownBody(),
            'tags' => fake()->randomElements(
                ['Laravel', 'PHP', 'Analytics', 'Server-Side', 'GDPR', 'Performance', 'API', 'Vue.js', 'Tracking', 'SaaS'],
                rand(1, 4)
            ),
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    private function generateMarkdownBody(): string
    {
        $paragraphs = fake()->paragraphs(rand(3, 6));
        $body = '## '.fake()->sentence(4)."\n\n";
        $body .= implode("\n\n", array_slice($paragraphs, 0, 2))."\n\n";
        $body .= '### '.fake()->sentence(3)."\n\n";
        $body .= "```php\n// Example: Querying analytics data\n\$visitors = StatsVisitor::query()\n    ->where('project_id', \$project->id)\n    ->whereDate('time', '>=', now()->subDays(30))\n    ->count();\n```\n\n";
        $body .= implode("\n\n", array_slice($paragraphs, 2))."\n\n";
        $body .= "[Learn more](https://simplestats.io/docs) about SimpleStats.\n";

        return $body;
    }
}
