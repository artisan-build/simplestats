---
title: "Server-Side Analytics for Any PHP Application with SimpleStats"
slug: "server-side-analytics-for-any-php-app"
date: "2026-04-12"
excerpt: "SimpleStats is known as a Laravel analytics tool, but you don't actually need Laravel to use it. The PHP client package works with any PHP 8.2+ application: Symfony, Slim, WordPress plugins, or plain PHP. Same server-side tracking, same GDPR compliance, no framework required."
tags:
  - PHP
  - Analytics
  - Server-Side Tracking
published: true
author_id: 1
---

Most of what we write about SimpleStats revolves around Laravel. That makes sense, it's where we started, and the Laravel client package handles almost everything automatically through middleware, observers, and event listeners.

But not every PHP project runs on Laravel.

Maybe you're maintaining a Symfony application. Maybe you're building a WordPress plugin that needs accurate conversion tracking. Maybe you have a plain PHP API that processes payments and you want to know which campaigns actually drive revenue. The common thread: you want server-side analytics that ad blockers can't touch, and you don't want to add a JavaScript snippet.

That's what the PHP client is for.

## What it does

The `simplestats-io/php-client` package is a lightweight wrapper around the SimpleStats API. It gives you five tracking methods:

1. **Visitor tracking** with GDPR-compliant hashed identification
2. **User registration tracking** linked to UTM campaigns
3. **Login tracking** for active user metrics (DAU/WAU/MAU)
4. **Payment tracking** with multi-currency support
5. **Custom event tracking** for anything else

No framework dependencies. Just Guzzle for HTTP and your API token.

## Quick setup

```bash
composer require simplestats-io/php-client
```

```php
use SimpleStatsIo\PhpClient\SimplestatsClient;
use SimpleStatsIo\PhpClient\TrackingData;
use SimpleStatsIo\PhpClient\VisitorHashGenerator;

$client = new SimplestatsClient([
    'api_token' => 'your-api-token',
]);
```

## Tracking visitors

SimpleStats identifies visitors with a hash built from IP, user agent, and a secret key. The hash rotates daily, so the same person gets a new identifier each day. The raw IP and user agent are never sent to the API.

```php
$visitorHash = VisitorHashGenerator::generate(
    ip: $_SERVER['REMOTE_ADDR'],
    userAgent: $_SERVER['HTTP_USER_AGENT'],
    secretKey: 'your-secret-key',
);

$trackingData = TrackingData::fromGlobals(appUrl: 'https://yourapp.com');

$client->trackVisitor($visitorHash, $trackingData);
```

`TrackingData::fromGlobals()` does the heavy lifting: it extracts UTM parameters, the HTTP referer, the entry page, and the client IP (with proxy/Cloudflare header support). It also filters out self-referrals so your own domain doesn't show up as a traffic source.

## Tracking registrations and payments

Once you have visitors, you probably want to know which ones sign up and which ones pay.

```php
// User registered
$client->trackUser(
    id: $user->id,
    registeredAt: $user->createdAt,
    trackingData: $trackingData,
    addLogin: true,
);

// Payment received
$client->trackPayment(
    id: $payment->id,
    gross: 2000,        // $20.00 in cents
    net: 1800,          // $18.00 in cents
    currency: 'USD',
    time: $payment->paidAt,
    userId: $user->id,
    userRegisteredAt: $user->createdAt,
);
```

Because payments are linked back to the user (and the user is linked to their original tracking data), SimpleStats can tell you which Google Ad or newsletter campaign actually generated that $20.

## Keeping responses fast

The API calls are synchronous by default. If you're running PHP-FPM, you can send the response to the browser first and track afterwards:

```php
// Deliver the response
fastcgi_finish_request();

// Track in the background
$client->trackVisitor($visitorHash, $trackingData);
```

If your application has a queue system (most frameworks do), dispatch the tracking as a background job instead. The client handles retries automatically (3 attempts with exponential backoff), so transient API failures won't lose your data.

## Where this fits

The PHP client is intentionally minimal. It doesn't try to auto-discover your user model or hook into framework events. You decide when and where to call it. That makes it flexible enough for:

- **Symfony** controllers and event subscribers
- **Slim/Mezzio** middleware
- **WordPress** plugins (track form submissions, WooCommerce orders)
- **Custom PHP APIs** that handle webhooks or process payments
- **CLI scripts** that import historical data

If you're on Laravel, the [Laravel client package](/docs/installation.html) is still the better choice since it automates most of this. But if you're on anything else, this gets you the same tracking with a few lines of code.

## Getting started

Install the package, grab your API token from the [Projects page](/projects), and check out the [PHP client documentation](/docs/php-client.html) for the full reference.
