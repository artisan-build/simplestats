<?php

namespace Database\Factories;

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

    public static int $id = 1;

    public function initialize()
    {
        self::$id = 1;
        return $this;
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween(now()->subYears(2), 'now')->format('Y-m-d H:i:s');

        $data = [
            'id' => self::$id++,
            'team_id' => Team::inRandomOrder()->first()->id,
            'project_id' => Project::inRandomOrder()->first()->id,
            'instance_id' => Instance::inRandomOrder()->first()->id,
            'track_source' => $this->faker->randomElement([null, 'google', 'newsletter', 'facebook', 'affiliate_partner', 'instagram']),
            'time' => $date,
        ];

        if (!is_null($data['track_source'])) {
            $data = array_merge($data, [
                'track_medium' => $this->faker->randomElement(['cpc', 'email', 'social', 'banner', 'referral']),
                'track_campaign' => $this->faker->randomElement(['summer_sale_2023', 'new_product_launch', 'christmas_special', 'brand_awareness', 'customer_retention']),
                'track_term' => $this->faker->randomElement(['outdoor_apparel', 'smartphone_deals', 'yoga_mats_sale', 'free_trial_software', 'organic_foods']),
                'track_content' => $this->faker->randomElement(['top_banner', 'newsletter_main_article', 'insta_story', 'page_2_ad', 'video_ad']),
            ]);
        }

        return $data;
    }
}
