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

## Payment Tracking

To track a new payment, the only thing you have to do is to add the `TrackablePayment` contract to your payment model and then implement the methods defined in the contract.

It may look like this:

```php
class Transaction extends Model implements TrackablePayment
{
    //..
    
    /**
     * The person associated with the payment.
     * This may either be a user or a visitor.
     */
    public function getTrackingPerson(): TrackablePerson
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

### Payment Tracking for Visitors

If a payment is made from a visitor and not a registered user you can handle this by returning a `Visitor` instead of a `User` object in the `getTrackingPerson` method (this feature is only available since version `3.0` of the _SimpleStats_ [client package](https://github.com/simplestats-io/laravel-client)).

::: tip
You can safely [skip](#with-condition) this section if that's never the case for you.
:::

#### Adding a Visitor Hash to Your Payment Model

When a new visitor visits your site, our package creates a [Visitor Hash](how-to-track-a-new-visitor.md#the-visitor-hash) and exposes it via `SimplestatsClient::getVisitorHash()`. You can use it to associate a payment with a visitor, which allows us to calculate the [ROI](https://en.wikipedia.org/wiki/Return_on_investment) of your marketing campaigns.

We recommend adding a `visitor_hash` column to your payment model. You can do that by publishing the following migration:

```bash
php artisan vendor:publish --tag=simplestats-client-migrations
php artisan migrate
```

Now make sure to add the hash when creating a new payment:

```php
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;

Transaction::create([
    ...
    // add the visitor_hash to the models fillable array
    'visitor_hash' => SimplestatsClient::getVisitorHash(),
]);
```

:::info
Sometimes, you might need to create the payment model exclusively within the completed webhook of your payment provider. As you can't use the current session there, you need to make sure to pass the `visitor_hash` as additional metadata to the request. Here are some common payment providers and how you can accomplish that: [Stripe](https://docs.stripe.com/metadata), [Paddle](https://developer.paddle.com/build/transactions/custom-data), [Lemon Squeezy](https://docs.lemonsqueezy.com/help/checkout/passing-custom-data)
:::

Finally, you need to make sure to return a `Visitor` object in your payment model if it's a visitor payment:

```php
use SimpleStatsIo\LaravelClient\Visitor;

class Transaction extends Model implements TrackablePayment
{
    //..
    
    /**
     * The person associated with the payment.
     * This may either be a user or a visitor.
     */
    public function getTrackingPerson(): TrackablePerson
    {
        if (! emtpy($this->visitor_hash)) {
            return new Visitor($this->visitor_hash);
        } else {
            return User::find($this->user_id);
        }
    }
    
    // ...
}
```

::: info
If your application does not have any users at all, just always return a `Visitor` object.
:::

Congrats, now each time a new **Transaction** model gets created, an API request is sent to _SimpleStats_ to count a new payment.

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
