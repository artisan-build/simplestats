<?php

use App\Models\DataImport;
use App\Models\Instance;
use App\Models\Project;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\SubscriptionService;
use Illuminate\Support\Number;

return [

    'dashboard_url' => RouteServiceProvider::HOME,

    /*
    |--------------------------------------------------------------------------
    | Spark Path
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the URI at which the Spark billing
    | portal is available. You are free to change this URI to a value that
    | you prefer. You shall link to this location from your application.
    |
    */

    'path' => 'billing',

    /*
    |--------------------------------------------------------------------------
    | Spark Middleware
    |--------------------------------------------------------------------------
    |
    | These are the middleware that requests to the Spark billing portal must
    | pass through before being accepted. Typically, the default list that
    | is defined below should be suitable for most Laravel applications.
    |
    */

    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | These configuration values allow you to customize the branding of the
    | billing portal, including the primary color and the logo that will
    | be displayed within the billing portal. This logo value must be
    | the absolute path to an SVG logo within the local filesystem.
    |
    */

    'brand' => [
        'logo' => realpath(__DIR__.'/../public/svg/app_mark.svg'),
        'color' => 'bg-indigo-500',
    ],

    /*
    |--------------------------------------------------------------------------
    | Proration Behavior
    |--------------------------------------------------------------------------
    |
    | This value determines if charges are prorated when making adjustments
    | to a plan such as incrementing or decrementing the quantity of the
    | plan. This also determines proration behavior if changing plans.
    |
    */

    'prorates' => true,

    /*
    |--------------------------------------------------------------------------
    | Spark Date Format
    |--------------------------------------------------------------------------
    |
    | This date format will be utilized by Spark to format dates in various
    | locations within the billing portal, such as while showing invoice
    | dates. You should customize the format based on your own locale.
    |
    */

    'date_format' => 'F j, Y',

    /*
    |--------------------------------------------------------------------------
    | Spark Billables
    |--------------------------------------------------------------------------
    |
    | Below you may define billable entities supported by your Spark driven
    | application. You are free to have multiple billable entities which
    | can each define multiple subscription plans available for users.
    |
    | In addition to defining your billable entity, you may also define its
    | plans and the plan's features, including a short description of it
    | as well as a "bullet point" listing of its distinctive features.
    |
    */

    'billables' => [

        'user' => [
            'model' => User::class,

            'default_interval' => 'monthly',

            // always make sure that the plan structure is compatible with the plan eligibility check in the SparkServiceProvider...
            'plans' => [
                [
                    //free plan must be the first in the array
                    'name' => SubscriptionService::PLAN_FREE,
                    'short_description' => 'Get started with simple analytics. No card required.',
                    'monthly_id' => 0,
                    'yearly_id' => 0,
                    'features' => [
                        Project::class => '1 Project',
                        //Instance::class => '1 Instance',
                        'Events' => Number::abbreviate(5_000).' Monthly Events',
                        'MultiCurrency' => 'Multi Currency Support',
                        DataImport::class => 'Data Import',
                    ],
                    'options' => [
                        Project::class => 1,
                        Instance::class => 1,
                        'Events' => 5_000,
                        'MultiCurrency' => false,
                        DataImport::class => false,
                    ],
                    'archived' => true,
                ],
                [
                    'name' => SubscriptionService::PLAN_PLUS,
                    'short_description' => 'For marketers with strong ambitions and growing needs.',
                    'monthly_id' => env('SPARK_PLUS_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_PLUS_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 13%',
                    'features' => [
                        Project::class => 'Unlimited Projects',
                        //Instance::class => 'Unlimited Instances',
                        'Events' => Number::abbreviate(200_000).' Monthly Events',
                        'MultiCurrency' => 'Multi Currency Support',
                        DataImport::class => 'Data Import',
                    ],
                    'options' => [
                        Project::class => SubscriptionService::UNLIMITED_AMOUNT,
                        Instance::class => SubscriptionService::UNLIMITED_AMOUNT,
                        'Events' => 200_000,
                        'MultiCurrency' => true,
                        DataImport::class => true,
                    ],
                    'archived' => false,
                ],
                [
                    'name' => SubscriptionService::PLAN_BUSINESS,
                    'short_description' => 'Scale your tracking and stay on top of growing activity.',
                    'monthly_id' => env('SPARK_BUSINESS_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_BUSINESS_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 15%',
                    'features' => [
                        Project::class => 'Unlimited Projects',
                        //Instance::class => 'Unlimited Instances',
                        'Events' => Number::abbreviate(500_000).' Monthly Events',
                        'MultiCurrency' => 'Multi Currency Support',
                        DataImport::class => 'Data Import',
                    ],
                    'options' => [
                        Project::class => SubscriptionService::UNLIMITED_AMOUNT,
                        Instance::class => SubscriptionService::UNLIMITED_AMOUNT,
                        'Events' => 500_000,
                        'MultiCurrency' => true,
                        DataImport::class => true,
                    ],
                    'archived' => false,
                    'promoted' => true,
                ],
                [
                    'name' => SubscriptionService::PLAN_ENTERPRISE,
                    'short_description' => 'Full-scale analytics without limits for large teams.',
                    'monthly_id' => env('SPARK_ENTERPRISE_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_ENTERPRISE_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 16%',
                    'features' => [
                        Project::class => 'Unlimited Projects',
                        //Instance::class => 'Unlimited Instances',
                        'Events' => Number::abbreviate(10_000_000).' Monthly Events',
                        'MultiCurrency' => 'Multi Currency Support',
                        DataImport::class => 'Data Import',
                    ],
                    'options' => [
                        Project::class => SubscriptionService::UNLIMITED_AMOUNT,
                        Instance::class => SubscriptionService::UNLIMITED_AMOUNT,
                        'Events' => 10_000_000,
                        'MultiCurrency' => true,
                        DataImport::class => true,
                    ],
                    'archived' => false,
                ],
            ],
        ],
    ],

    'discount' => [
        'percent' => 50,
        'duration' => [
            'start' => '2024-11-21',
            'end' => '2024-12-02',
        ],
        'text' => '🎉 50% OFF ALL PLANS! Use code "BLACKWEEK2024" at checkout. 🎉',
        'link' => '#pricing',
    ],
];
