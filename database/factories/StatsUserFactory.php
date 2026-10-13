<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\StatsUser;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatsUser>
 */
class StatsUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StatsUser::class;

    public static int $id = 1;

    public function initialize(): static
    {
        self::$id = 1;

        return $this;
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween(now()->subYears(2), 'now')->format('Y-m-d H:i:s');

        $data = [
            'id' => self::$id++,
            // Lazy closures so the queries are skipped whenever the seeder/test
            // overrides the key. Direct expressions would run on every create().
            'team_id' => fn () => Team::inRandomOrder()->first()->id,
            'project_id' => fn () => Project::inRandomOrder()->first()->id,
            'location_country' => fake()->randomElement([null, 'Germany (DE)', 'China (CN)', 'United States (US)', 'Brazil (BR)', 'Nigeria (NG)', 'Australia (AU)']),
            'location_region' => fake()->randomElement([null, 'Bavaria (DE)', 'Beijing (CN)', 'New York (US)', 'São Paulo (BR)', 'Rivers (NG)', 'New South Wales (AU)']),
            'location_city' => fake()->randomElement([null, 'Berlin (DE)', 'Shanghai (CN)', 'Albany (US)', 'Rio de Janeiro (BR)', 'Port Harcourt (NG)', 'Sydney (AU)']),
            'device_type' => fake()->randomElement([null, 'Desktop', 'Mobile', 'Tablet']),
            'device_platform' => fake()->randomElement([null, 'Mac', 'Windows', 'Linux']),
            'device_browser' => fake()->randomElement([null, 'Chrome', 'Safari', 'Firefox', 'Opera', 'Microsoft Edge']),
            'track_referer' => fake()->randomElement([null, 'google.com', 'instagram.com', 'youtube.com', 'linkedin.com', 'bing.com', 'facebook.com', 'duckduckgo.com', 'laracraft.tech']),
            'track_source' => fake()->randomElement([null, 'twitter', 'newsletter', 'facebook', 'affiliate_partner', 'instagram']),
            'page_entry' => fake()->randomElement(['/', '/blog/my-super-successful-article', '/blog/why-we-are-awesome', '/blog/we-love-good-analytics']),
            'time' => $date,
            'time_utc' => $date,
        ];

        if (! is_null($data['track_source'])) {
            return array_merge($data, [
                'track_medium' => fake()->randomElement(['cpc', 'email', 'social', 'banner', 'referral']),
                'track_campaign' => fake()->randomElement(['summer_sale_2023', 'new_product_launch', 'christmas_special', 'brand_awareness', 'customer_retention']),
                'track_term' => fake()->randomElement(['outdoor_apparel', 'smartphone_deals', 'yoga_mats_sale', 'free_trial_software', 'organic_foods']),
                'track_content' => fake()->randomElement(['top_banner', 'newsletter_main_article', 'insta_story', 'page_2_ad', 'video_ad']),
            ]);
        }

        return $data;
    }
}
