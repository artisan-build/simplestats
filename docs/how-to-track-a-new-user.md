# How to track a new user

Now that you've installed the _SimpleStats_ **client package**, you can finally start tracking your users! Don't worry, it's super simple.

## Configuration

If you for any reason do **not** use the default user model from Laravel, you may define your user model here:

```php
//config/simplestats-client.php

    'tracking_types' => [
        // ...
        
        'user' => [
            'model' => App\Models\CustomUser::class,
        ],
        
        // ...
    ],

```

## Trackable User

To track a new user/registration, the only thing you have to do is to add the `TrackableUser` contract to your `User` model and then implement the methods defined in the contract.

It may look like this:

```php
use SimpleStatsIo\LaravelClient\Contracts\TrackableUser;

class User extends Authenticatable implements TrackableUser
{
    // ...
    
    /**
     * The time when the user has registered.
     */
    public function getTrackingTime(): CarbonInterface
    {
        return $this->created_at;
    }
    
    // ...
}
```

Now each time a new **User** model gets created, an API request is sent to _SimpleStats_ to count a new registration.

::: info
You may tweak the `getTrackingTime` method to your needs. For instance, if your `created_at` field is named differently you have to ajust it in the method.
:::

::: warning
In addition, your trackable user model's primary key should be an `integer` column named `id`. We do not support `uuid` or `ulid` at the moment.
:::

### With Condition

Sometimes you may not want to count a newly created user immediately as a "valid" registration. For such cases, the `TrackableUserWithCondition` contract is designed to be utilized. Use it if you want to track/count a new registration only when a certain condition is fulfilled, such as the user has verified their email address.

```php
use SimpleStatsIo\LaravelClient\Contracts\TrackableUserWithCondition;

class User extends Authenticatable implements TrackableUserWithCondition
{
    // ...

    /**
     * The condition that should be fulfilled in order to track the user.
     */
    public function passTrackingCondition(): bool
    {
        return $this->email_verified_at != NULL;
    }

    /**
     * The field(s) we should listen for changes to recheck the condition.
     */
    public function watchedTrackingFields(): array
    {
        return ['email_verified_at'];
    }
    
    // ...
}
```

::: info
Now, as soon as the user verified their email address and the `email_verified_at` field changes for instance from `NULL` to a date, the user gets tracked and counted. You may tweak these methods completely to your needs! 
:::

::: tip
You can check as many fields as you like and apply any logic you want here.
:::
