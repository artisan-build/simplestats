<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsUser;
use App\Models\Team;

class StatsUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StatsUser::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween(now()->subYears(2), 'now')->format('Y-m-d H:i:s');

        return [
            'team_id' => Team::factory(),
            'project_id' => Project::factory(),
            'instance_id' => Instance::factory(),
            'track_source' => $this->faker->randomElement(['google', 'newsletter', 'facebook', 'affiliate_partner', 'instagram']),
            'track_medium' => $this->faker->randomElement(['cpc', 'email', 'social', 'banner', 'referral']),
            'track_campaign' => $this->faker->randomElement(['summer_sale_2023', 'new_product_launch', 'christmas_special', 'brand_awareness', 'customer_retention']),
            'track_term' => $this->faker->randomElement(['outdoor_apparel', 'smartphone_deals', 'yoga_mats_sale', 'free_trial_software', 'organic_foods']),
            'track_content' => $this->faker->randomElement(['top_banner', 'newsletter_main_article', 'insta_story', 'page_2_ad', 'video_ad']),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
