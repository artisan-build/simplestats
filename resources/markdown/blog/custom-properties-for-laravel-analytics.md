---
title: "Custom Properties: Group Your Laravel Analytics by Plan, Company or Anything You Want"
slug: "custom-properties-for-laravel-analytics"
date: "2026-06-07"
excerpt: "Until now, SimpleStats could break your KPIs down by the dimensions we track for you: UTM parameters, countries, devices, entry pages. But the dimensions that actually drive decisions are usually yours: the subscription plan, the company size, the A/B test variant. With custom properties you can now attach your own attributes to users and visitors, and group visitors, registrations, conversion rate and revenue by them."
tags:
  - Laravel
  - Analytics
  - Properties
  - A/B Testing
published: true
author_id: 1
---

SimpleStats answers questions like "which campaign brings paying users?" out of the box, because UTM parameters, countries, devices and entry pages are dimensions the client package can capture on its own. But the moment the question becomes "which **plan** converts best?" or "did variant B of the new landing page actually produce more revenue?", the answer lives in _your_ database, not ours.

The new custom properties feature closes that gap. You attach your own `name => value` attributes to users and visitors (`subscription => pro`, `company => Acme`, `ab_test => B`), and the dashboard breaks visitors, registrations, conversion rate and net revenue down by those values. Custom property tracking requires **version 4.1+** of the [client package](https://github.com/simplestats-io/laravel-client).

## Resolvers: set it and forget it

The laziest way to use custom properties is to not call anything at all. You register resolver classes in `config/simplestats-client.php`, one for users, one for visitors, or both:

```php
'custom_properties_resolvers' => [
    'user' => App\Analytics\UserCustomPropertiesResolver::class,
    'visitor' => App\Analytics\VisitorCustomPropertiesResolver::class,
],
```

The user resolver receives the user model you already track and returns the current properties:

```php
namespace App\Analytics;

use SimpleStatsIo\LaravelClient\Contracts\ResolvesUserCustomProperties;
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;

class UserCustomPropertiesResolver implements ResolvesUserCustomProperties
{
    public function resolve(TrackablePerson $user): array
    {
        return [
            'subscription' => $user->plan->name, // "free", "pro", "enterprise"
            'company' => $user->company_name,
        ];
    }
}
```

Whenever the package tracks the user, the resolved properties travel along with the same API request. No extra call, no extra queue job.

The visitor resolver is invoked at the moment a new visitor is tracked. That is the right place to assign an A/B test variant on the landing page:

```php
namespace App\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use SimpleStatsIo\LaravelClient\Contracts\ResolvesVisitorCustomProperties;

class VisitorCustomPropertiesResolver implements ResolvesVisitorCustomProperties
{
    public function resolve(Request $request): array
    {
        $variant = $request->session()->remember('ab_variant', fn () => Arr::random(['A', 'B']));

        return ['ab_test' => $variant];
    }
}
```

## Manual tracking for everything in between

Not every property change coincides with a track the package does on its own. For those cases there is an explicit call:

```php
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;
use SimpleStatsIo\LaravelClient\Visitor;

// on a plan change
SimplestatsClient::trackCustomProperties(
    properties: ['subscription' => 'pro'],
    person: auth()->user(),
);

// for the current (anonymous) visitor
SimplestatsClient::trackCustomProperties(
    properties: ['ab_test' => 'B'],
    person: new Visitor(SimplestatsClient::getVisitorHash()),
);
```

The usual guard rails apply: properties for a visitor that was never tracked (a bot, a blocked IP, an excluded route) are silently skipped, since nothing could ever be attributed to them anyway.

## The A/B test funnel: inheritance on sign-up

The part that makes visitor properties genuinely useful: they are **inherited by the registration of the same visit**.

Say the visitor resolver assigns `ab_test => B` on the landing page. The visitor browses around, signs up twenty minutes later. The client remembers the visitor's properties and sends them along with the user track automatically. From that point on the registration, the conversion rate and every later payment of that user can be grouped by a property that was assigned before the user even existed.

So the full A/B test funnel, with zero manual calls:

1. Visitor lands, resolver assigns `ab_test => B`, visitor is tracked with it.
2. Visitor signs up, registration inherits `ab_test => B`.
3. User pays, payment is linked to the user as always.
4. Dashboard: group by `ab_test`, compare visitors, registrations, CR and net revenue of `A` vs `B`.

## In the dashboard

Custom properties show up as a new grouping table next to the existing ones (tracking codes, locations, devices, pages). Each property name becomes a tab, each value becomes a row with visitors, registrations, conversion rate and revenue. Visitor-only properties hide the columns that make no sense for them, user-only properties do the same in reverse.

Clicking a value pivots the **entire dashboard** to that segment: every KPI, every chart, every other grouping table is then filtered to e.g. `subscription is pro`, the same way the existing filters work. Group by plan, click `enterprise`, and see which campaigns the enterprise customers originally came from.

## Dimensions, not an event log

A design decision worth spelling out: a user or visitor can carry as many properties as you like (`subscription`, `company` and `ab_test` all at once), but each property holds exactly **one current value** per user or visitor. Re-tracking `subscription` with a new value overwrites the old one.

That sounds like a limitation, but it is the behavior you want for segmentation. When a user upgrades from `free` to `pro`, their revenue and activity should count under `pro`, not stay frozen under the plan they happened to have at signup. The grouping always reflects the latest known state, the same way your own database does.

If you need the history of an action instead ("how often was the upgrade button clicked?"), that is what [custom events](/docs/how-to-track-a-custom-event.html) are for. Properties are dimensions, events are facts.

## Limits

The practical limits:

- Values must be **scalar** (string, number or boolean). Non-scalar or empty values are ignored.
- Names and values are trimmed to **252 characters**.
- A single track request may include up to **50 properties**.

Custom properties are dimensions, not a data warehouse: a handful of well-chosen properties (`plan`, `industry`, `ab_test`, `signup_source`) beats mirroring half your users table.

## Wrapping up

Custom properties turn SimpleStats from "analytics by acquisition dimensions" into "analytics by your dimensions". The setup is one config entry and one small resolver class, everything else (sending, inheritance on sign-up, overwriting on change) happens automatically.

The full reference lives in the [How to track custom properties](/docs/how-to-track-custom-properties.html) docs.
