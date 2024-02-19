<?php

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

            'plans' => [
                [
                    //free plan must be the first in the array
                    'name' => 'Basic',
                    'short_description' => 'Starter to get in touch. Explore the strength of statistics. No card required.',
                    'monthly_id' => 0,
                    'yearly_id' => 0,
                    'features' => [
                        '1 Project',
                        '1 Instance',
                        '1,000 Users',
                        '100 Payments',
                        '5,000 Logins',
                    ],
                    'options' => [
                        'projects' => 1,
                        'instances' => 1,
                        'users' => 1_000,
                        'payments' => 100,
                        'logins' => 5_000,
                    ],
                    'archived' => true,
                ],
                [
                    'name' => 'Plus',
                    'short_description' => 'Advanced marketeer with strong ambitions and great plans to expand.',
                    'monthly_id' => env('SPARK_HOBBY_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_HOBBY_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 13%',
                    'features' => [
                        '2 Projects',
                        '5 Instances',
                        '100,000 Users',
                        '50,000 Payments',
                        '500,000 Logins',
                    ],
                    'options' => [
                        'projects' => 2,
                        'instances' => 5,
                        'users' => 100_000,
                        'payments' => 50_000,
                        'logins' => 500_000,
                    ],
                    'archived' => false,
                ],
                [
                    'name' => 'Business',
                    'short_description' => 'Finally see which of your marketing activities leads to revenue.',
                    'monthly_id' => env('SPARK_GROWTH_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_GROWTH_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 15%',
                    'features' => [
                        '8 Projects',
                        '20 Instances',
                        '1,000,000 Users',
                        '500,00 Payments',
                        '5,000,000 Logins',
                    ],
                    'options' => [
                        'projects' => 8,
                        'instances' => 20,
                        'users' => 1_000_000,
                        'payments' => 500_000,
                        'logins' => 5_000_000,
                    ],
                    'archived' => false,
                    'promoted' => true,
                ],
                [
                    'name' => 'Enterprise',
                    'short_description' => 'Get an unlimited high level overview of your marketing efforts.',
                    'monthly_id' => env('SPARK_BUSINESS_MONTHLY_PLAN'),
                    'yearly_id' => env('SPARK_BUSINESS_YEARLY_PLAN'),
                    'yearly_incentive' => 'Save 16%',
                    'features' => [
                        '20 Projects',
                        '100 Instances',
                        '10,000,000 Users',
                        'Unlimited Payments',
                        'Unlimited Logins',
                    ],
                    'options' => [
                        'projects' => 20,
                        'instances' => 100,
                        'users' => 10_000_000,
                        'payments' => SubscriptionService::UNLIMITED_AMOUNT,
                        'logins' => SubscriptionService::UNLIMITED_AMOUNT,
                    ],
                    'archived' => false,
                ],
            ],

        ],

    ],
];
