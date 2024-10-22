# How to track a new payment

As the payment model is named very individually, we did not set any default for it. You should give it the name of the model which holds your payments/transactions data.

## Configuration

Set the name of your payment model in the config:

```php
//config/simplestats-client.php

    'tracking_types' => [
        // ...
        
        'payment' => [
            'model' => App\Models\Transaction::class,
        ],
        
        // ...
    ],

```

:::info
For this guide lets assume your payment model is called `Transaction`.
:::

## Trackable Payment

To track a new payment, the only thing you have to do is to add the `TrackablePayment` contract to your payment model and then implement the methods defined in the contract.

It may look like this:

```php
use SimpleStatsIo\LaravelClient\Contracts\TrackablePayment;use SimpleStatsIo\LaravelClient\Visitor;

class Transaction extends Model implements TrackablePayment
{
    //..
    
    /**
     * The user associated with the payment.
     */
    public function getTrackingUser(): User
    {
        return User::find($this->user_id);
    }

    /**
     * The time when the payment happened.
     */
    public function getTrackingTime(): CarbonInterface
    {
        return $this->created_at;
    }

    /**
     * The gross amount of the payment in cents ($1 = 100 Cent).
     */
    public function getTrackingGross(): float
    {
        return $this->total;
    }

    /**
     * The net amount of the payment in cents ($1 = 100 Cent).
     */
    public function getTrackingNet(): float
    {
        return $this->total - $this->tax;
    }

    /**
     * The ISO-4217 currency code of the payment.
     * E.g.: EUR, USD, GBP, etc...
     */
    public function getTrackingCurrency(): string
    {
        return $this->currency;
    }
    
    //..
}
```

Now each time a new **Transaction** model gets created, an API request is sent to _SimpleStats_ to count a new payment.

::: info
You may tweak the defined methods to your needs and apply any logic you need there!
:::

::: danger
Remember that **Gross** and **Net** must return cents!
:::

### With Condition  

Sometimes you may not want to count a newly created payment immediately as a "valid" one. For such cases, the `TrackablePaymentWithCondition` contract is designed to be utilized. Use it if you want to track/count a new payment only when a certain condition is fulfilled, such as the payment _status_ has switched from "pending" to "completed".

```php
use SimpleStatsIo\LaravelClient\Contracts\TrackablePaymentWithCondition;

class Transaction extends Model implements TrackablePaymentWithCondition
{
    // ...

    /**
     * The condition that should be fulfilled in order to track the payment.
     */
    public function passTrackingCondition(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * The field(s) we should watch for changes to recheck the condition.
     */
    public function watchTrackingFields(): array
    {
        return ['status'];
    }
    
    // ...
}
```

:::info
Now, as soon as the payment _status_ switches from "pending" to "completed", the payment gets tracked and counted. You may tweak these methods completely to your needs!
:::

::: tip
You can check as many fields as you like and apply any logic you want here.
:::

## Multi Currency Support

Upon registering with _SimpleStats_, you'll be prompted to select a default currency for your team/company. For services, shops, or applications that operate across multiple currencies, **our API** seamlessly handles the conversion. Any transactions made in foreign currencies are automatically **converted** to your team's default currency, using a **daily exchange rate**. This feature ensures that all financial data is standardized and accurately reflects in your analytics, simplifying the management of international transactions.

::: info
_Multi Currency Support_ is not available on the free subscription plan.
:::
