<?php

use App\Models\DataImport;
use App\Models\Instance;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsPayment;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\SubscriptionService;

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

     'brand' =>  [
         'logo' => realpath(__DIR__.'/../public/svg/logo.svg'),
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
                    'short_description' => 'Starter to get in touch. Explore the strength of analytics. No card required.',
                    'monthly_id' => 0,
                    'yearly_id' => 0,
                    'features' => [
                        Project::class => '1 Project',
                        Instance::class => '1 Instance',
                        StatsUser::class => '5,000 Users',
                        StatsPayment::class => '1,000 Payments',
                        StatsLogin::class => '10,000 Logins',
                        StatsVisitor::class => '30,000 Visitors',
                        DataImport::class => 'Data Import',
                        'MultiCurrency' => 'Multi Currency Support',
                    ],
                    'options' => [
                        Project::class => 1,
                        Instance::class => 1,
                        StatsUser::class => 5_000,
                        StatsPayment::class => 1_000,
                        StatsLogin::class => 10_000,
                        StatsVisitor::class => 30_000,
                        DataImport::class => false,
                        'MultiCurrency' => false,
                    ],
                    'archived' => true,
                ],
                [
                    'name' => SubscriptionService::PLAN_PLUS,
                    'short_description' => 'Advanced marketeer with strong ambitions and great plans to expand.',
                    'monthly_id' => env('SPARK_PLUS_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_PLUS_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 13%',
                    'features' => [
                        Project::class => '2 Projects',
                        Instance::class => '5 Instances',
                        StatsUser::class => '100,000 Users',
                        StatsPayment::class => '50,000 Payments',
                        StatsLogin::class => '500,000 Logins',
                        StatsVisitor::class => '1,500,000 Visitors',
                        DataImport::class => 'Data Import',
                        'MultiCurrency' => 'Multi Currency Support',
                    ],
                    'options' => [
                        Project::class => 2,
                        Instance::class => 5,
                        StatsUser::class => 100_000,
                        StatsPayment::class => 50_000,
                        StatsLogin::class => 500_000,
                        StatsVisitor::class => 1_500_000,
                        DataImport::class => true,
                        'MultiCurrency' => true,
                    ],
                    'archived' => false,
                ],
                [
                    'name' => SubscriptionService::PLAN_BUSINESS,
                    'short_description' => 'Finally see which of your campaign activities are generating revenue.',
                    'monthly_id' => env('SPARK_BUSINESS_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_BUSINESS_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 15%',
                    'features' => [
                        Project::class => '8 Projects',
                        Instance::class => '20 Instances',
                        StatsUser::class => '1,000,000 Users',
                        StatsPayment::class => '500,000 Payments',
                        StatsLogin::class => '5,000,000 Logins',
                        StatsVisitor::class => '15,000,000 Visitors',
                        DataImport::class => 'Data Import',
                        'MultiCurrency' => 'Multi Currency Support',
                    ],
                    'options' => [
                        Project::class => 8,
                        Instance::class => 20,
                        StatsUser::class => 1_000_000,
                        StatsPayment::class => 500_000,
                        StatsLogin::class => 5_000_000,
                        StatsVisitor::class => 15_000_000,
                        DataImport::class => true,
                        'MultiCurrency' => true,
                    ],
                    'archived' => false,
                    'promoted' => true,
                ],
                [
                    'name' => SubscriptionService::PLAN_ENTERPRISE,
                    'short_description' => 'Get an unlimited high level overview of your marketing efforts.',
                    'monthly_id' => env('SPARK_ENTERPRISE_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_ENTERPRISE_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 16%',
                    'features' => [
                        Project::class => '20 Projects',
                        Instance::class => '100 Instances',
                        StatsUser::class => '10,000,000 Users',
                        StatsPayment::class => 'Unlimited Payments',
                        StatsLogin::class => 'Unlimited Logins',
                        StatsVisitor::class => 'Unlimited Visitors',
                        DataImport::class => 'Data Import',
                        'MultiCurrency' => 'Multi Currency Support',
                    ],
                    'options' => [
                        Project::class => 20,
                        Instance::class => 100,
                        StatsUser::class => 10_000_000,
                        StatsPayment::class => SubscriptionService::UNLIMITED_AMOUNT,
                        StatsLogin::class => SubscriptionService::UNLIMITED_AMOUNT,
                        StatsVisitor::class => SubscriptionService::UNLIMITED_AMOUNT,
                        DataImport::class => true,
                        'MultiCurrency' => true,
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
