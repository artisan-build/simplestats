---
title: "How to Track Laravel Cashier Payments with SimpleStats"
slug: "how-to-track-laravel-cashier-payments"
date: "2026-04-10"
excerpt: "One of the most common questions we get is how to integrate SimpleStats with Laravel Cashier. The good news: SimpleStats is payment-provider agnostic. Whether you use Stripe, Paddle, or Lemon Squeezy, the pattern is always the same. Implement a contract on your payment model, point to it in your config, and you're done."
tags:
  - Laravel
  - Stripe
  - Paddle
  - Payments
published: true
author_id: 1
---

One of the most common questions we get is: *"How does SimpleStats work with Laravel Cashier?"*

The good news: SimpleStats is payment-provider agnostic. You implement a contract on your payment model, register it in your config, and SimpleStats takes care of the rest.

The only thing that differs between providers is where the model comes from. **Paddle** ships an Eloquent model you can extend directly. **Stripe** doesn't, so you need to create your own. This guide walks you through both, starting with the easier one.

## The Pattern

No matter which payment provider you use, the integration always follows the same two steps:

1. Implement the `TrackablePayment` contract on your payment model
2. Register that model in your `simplestats-client.php` config

The contract defines five methods:

```php
interface TrackablePayment
{
    public function getTrackingPerson(): TrackablePerson;
    public function getTrackingTime(): CarbonInterface;
    public function getTrackingGross(): float;
    public function getTrackingNet(): float;
    public function getTrackingCurrency(): string;
}
```

- **`getTrackingPerson()`** returns the user (or visitor) associated with the payment
- **`getTrackingTime()`** returns when the payment happened
- **`getTrackingGross()`** returns the gross amount in **cents** ($1 = 100)
- **`getTrackingNet()`** returns the net amount in **cents**
- **`getTrackingCurrency()`** returns the ISO-4217 currency code (EUR, USD, GBP, etc.)

Once your model implements this contract, register it in your config:

```php
// config/simplestats-client.php

'tracking_types' => [
    // ...

    'payment' => [
        'model' => App\Models\Transaction::class,
    ],

    // ...
],
```

That's it. Every time a new instance of that model gets created, SimpleStats automatically tracks the payment. Now let's look at how this works with each provider.

---

## Cashier Paddle

Paddle is the easiest integration because Cashier Paddle ships with a `Transaction` Eloquent model that you can extend directly.

### Step 1: Create Your Transaction Model

Create a new model that extends Paddle's `Transaction` and implements the `TrackablePaymentWithCondition` contract:

```php
<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Laravel\Paddle\Transaction as PaddleTransaction;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePaymentWithCondition;

class Transaction extends PaddleTransaction implements TrackablePaymentWithCondition
{
    public function getTrackingPerson(): TrackablePerson
    {
        return $this->billable;
    }

    public function getTrackingTime(): CarbonInterface
    {
        return $this->created_at;
    }

    public function getTrackingGross(): float
    {
        return $this->total;
    }

    public function getTrackingNet(): float
    {
        return $this->total - $this->tax;
    }

    public function getTrackingCurrency(): string
    {
        return $this->currency;
    }

    public function passTrackingCondition(): bool
    {
        return $this->status === 'completed';
    }

    public function watchTrackingFields(): array
    {
        return ['status'];
    }
}
```

We use `TrackablePaymentWithCondition` here instead of the simpler `TrackablePayment` because Paddle transactions start as "pending" and only become "completed" after the payment goes through. The `passTrackingCondition()` method ensures we only track completed payments, and `watchTrackingFields()` tells SimpleStats to re-check the condition whenever the `status` field changes.

### Step 2: Register the Model

Tell Cashier Paddle to use your extended model. In your `AppServiceProvider`:

```php
use App\Models\Transaction;
use Laravel\Paddle\Cashier;

public function boot(): void
{
    Cashier::useTransactionModel(Transaction::class);
}
```

Done. Every completed Paddle transaction now gets tracked automatically.

> **Using Lemon Squeezy?** The same approach works. The `lmsqueezy/laravel` package ships with an `Order` model you can extend the same way. Just extend `LemonSqueezy\Laravel\Order`, implement the contract, and register it via `LemonSqueezy::useOrderModel()`.

---

## Cashier Stripe

Stripe requires a bit more work. Unlike Paddle and Lemon Squeezy, Cashier Stripe does **not** ship with an Eloquent model for individual payments. It stores subscriptions locally (in the `subscriptions` table), but individual charges, invoices, and checkout payments live entirely in Stripe's API. The `Laravel\Cashier\Payment` class you might have seen in the docs is just a wrapper around Stripe's PaymentIntent object, not an Eloquent model.

That means you need to create your own payment model and populate it from Stripe webhooks.

### Understanding Stripe's Webhook Events

Before we dive into code, there's an important detail about Stripe's webhooks: **they overlap**. When a subscription renews, Stripe fires both `invoice.payment_succeeded` and `charge.succeeded`. When a Checkout session completes, it fires `checkout.session.completed` and `charge.succeeded`. If you listen to multiple events, you'll end up with duplicate payments in your database.

The cleanest solution: listen to **`charge.succeeded` only**. Every successful payment in Stripe (subscriptions, one-time charges via `$user->charge()`, invoice-based charges via `$user->invoicePrice()`, Checkout sessions, Payment Intents) creates exactly one Charge. One event, complete coverage, no duplicates.

### Step 1: Create the Migration

```bash
php artisan make:migration create_payments_table
```

```php
public function up(): void
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('stripe_charge_id')->unique();
        $table->unsignedBigInteger('amount');
        $table->unsignedBigInteger('tax')->default(0);
        $table->string('currency', 3);
        $table->timestamps();
    });
}
```

### Step 2: Create the Payment Model

```php
<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePayment;

class Payment extends Model implements TrackablePayment
{
    protected $fillable = [
        'user_id',
        'stripe_charge_id',
        'amount',
        'tax',
        'currency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTrackingPerson(): TrackablePerson
    {
        return $this->user;
    }

    public function getTrackingTime(): CarbonInterface
    {
        return $this->created_at;
    }

    public function getTrackingGross(): float
    {
        return $this->amount;
    }

    public function getTrackingNet(): float
    {
        return $this->amount - $this->tax;
    }

    public function getTrackingCurrency(): string
    {
        return strtoupper($this->currency);
    }
}
```

Notice we use `TrackablePayment` here (without condition) instead of `TrackablePaymentWithCondition`. Since we only create the model when the `charge.succeeded` webhook fires, the payment is already confirmed at that point. No need to watch for status changes.

### Step 3: Create the Webhook Listener

Create a listener that reacts to Cashier's `WebhookReceived` event:

```php
<?php

namespace App\Listeners;

use App\Models\Payment;
use App\Models\User;
use Laravel\Cashier\Events\WebhookReceived;

class StripeChargeSucceeded
{
    public function handle(WebhookReceived $event): void
    {
        if ($event->payload['type'] !== 'charge.succeeded') {
            return;
        }

        $charge = $event->payload['data']['object'];

        $user = User::where('stripe_id', $charge['customer'])->first();

        if (! $user) {
            return;
        }

        Payment::updateOrCreate(
            ['stripe_charge_id' => $charge['id']],
            [
                'user_id' => $user->id,
                'amount' => $charge['amount'],
                'tax' => $charge['amount'] - ($charge['amount_captured'] ?? $charge['amount']),
                'currency' => $charge['currency'],
            ]
        );
    }
}
```

The listener maps the Stripe customer back to your local user via the `stripe_id` column that Cashier adds to your `users` table.

> **Tax handling note:** Stripe's `charge.succeeded` payload doesn't include a separate tax field. If you need accurate tax breakdowns, you can pull the tax amount from the related invoice. For many use cases, passing the full charge amount as both gross and net is a perfectly valid starting point.

### Step 4: Register the Listener

In your `EventServiceProvider`:

```php
use App\Listeners\StripeChargeSucceeded;
use Laravel\Cashier\Events\WebhookReceived;

protected $listen = [
    WebhookReceived::class => [
        StripeChargeSucceeded::class,
    ],
];
```

### Step 5: Enable the Webhook in Stripe

Cashier automatically registers the `/stripe/webhook` route, but you need to make sure `charge.succeeded` is enabled in your Stripe Dashboard under Developers > Webhooks.

Now every Stripe payment (subscriptions, one-time charges, Checkout sessions, everything) gets tracked through SimpleStats.

---

## When to Use Conditional Tracking

In the Paddle and Lemon Squeezy examples above, we used `TrackablePaymentWithCondition` instead of the simpler `TrackablePayment`. This is useful whenever your payment model gets created before the payment is actually confirmed.

For example, Paddle creates a transaction record immediately, then updates its status to "completed" once the payment goes through. Without the condition, SimpleStats would track the payment the moment the record is created, even if it's still pending.

The `TrackablePaymentWithCondition` contract adds two methods:

```php
public function passTrackingCondition(): bool
{
    return $this->status === 'completed';
}

public function watchTrackingFields(): array
{
    return ['status'];
}
```

`watchTrackingFields()` tells SimpleStats which fields to monitor. When any of those fields change, SimpleStats re-evaluates the condition. Once `passTrackingCondition()` returns `true`, the payment gets tracked.

You can use any logic you want here. Check multiple fields, combine conditions, whatever fits your business logic.

---

## Works with Any Provider

While this guide focuses on Laravel Cashier (Stripe and Paddle), the pattern works with any payment provider. If you use a different gateway, the approach is the same: create or extend a model, implement the contract, register it in the config.

For the full payment tracking documentation, including visitor-based payment tracking for guest checkouts and multi-currency support, check out our [payment tracking guide](https://simplestats.io/docs/how-to-track-a-new-payment.html).
