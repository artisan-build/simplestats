# How to track a new login

We **proactively** track each login to your application, by listening to the default Laravel login event: `Illuminate\Auth\Events\Login`. This allows us to filter the data in our dashboard by certain time periods and check how the users perform over _days, weeks and months_.


## Configuration

If you for any reason do **not** use the default Login event from Laravel, you may define your own here:

```php
//config/simplestats-client.php

    'tracking_types' => [
        // ...

        'login' => [
            'event' => App\Events\CustomLogin::class,
        ],
        
        // ...
    ],

```
