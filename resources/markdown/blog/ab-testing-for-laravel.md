---
title: "A/B Testing for Laravel: See Which Variant Actually Makes Money"
slug: "ab-testing-for-laravel"
date: "2026-06-07"
excerpt: "You A/B test your landing page, but your analytics only tells you which variant got more visitors, not which one produced more paying users. With custom properties you tag each visitor with their variant, and SimpleStats follows it through signup all the way to revenue. You assign the variant; we measure it. And the same mechanism works for any segment: plan, company, anything you track."
tags:
  - Laravel
  - A/B Testing
  - Analytics
  - Properties
published: true
author_id: 1
---

You ship two versions of your landing page and split traffic between them. A week later you want one number: did variant B actually bring more **paying** users, or just more clicks? Most analytics stop at the top of the funnel. They tell you variant B got more visitors, maybe a better signup rate, and then lose the thread the moment money is involved.

That last step is exactly where attribution usually breaks. SimpleStats closes it with **custom properties**: you tag a visitor with their variant on the landing page, and that tag rides along through signup and every later payment. Group by the variant in the dashboard and you see visitors, registrations, conversion rate _and_ net revenue for `A` vs `B`, side by side.

One thing up front so nobody is surprised: SimpleStats does not run the test for you. No traffic splitting, no significance math. **You assign the variant; SimpleStats measures it**, all the way to revenue. And though A/B tests are the obvious use, the same mechanism groups your analytics by any attribute you track, plan, company, signup source. More on that at the end.

Custom property tracking requires **version 4.1+** of the [client package](https://github.com/simplestats-io/laravel-client).

## Tag the variant on the landing page

You register resolver classes in `config/simplestats-client.php`, one for visitors, one for users, or both:

```php
'custom_properties_resolvers' => [
    'visitor' => App\Analytics\VisitorCustomPropertiesResolver::class,
    'user' => App\Analytics\UserCustomPropertiesResolver::class,
],
```

The visitor resolver is invoked the moment a new visitor is tracked, which is exactly when you want to decide and record the variant:

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

That is the only A/B-specific code you write. The variant is now attached to the visitor, and you never have to pass it anywhere again.

## Follow the variant through to revenue

Here is the part that makes this actually work: visitor properties are **inherited by the registration of the same visit**.

The visitor lands, gets `ab_test => B`, browses around, signs up twenty minutes later. The client remembers the variant and sends it along with the user track automatically. From that registration on, the conversion rate and every later payment of that user stay grouped under `B`, a property that was assigned before the user even existed.

So the full funnel, with zero manual calls after the resolver:

1. Visitor lands, resolver assigns `ab_test => B`, visitor is tracked with it.
2. Visitor signs up, registration inherits `ab_test => B`.
3. User pays, payment is linked to the user as always.
4. Dashboard: group by `ab_test`, compare visitors, registrations, CR and net revenue of `A` vs `B`.

## See it in the dashboard

Custom properties show up as a new grouping table next to the existing ones (tracking codes, locations, devices, pages). Each property name becomes a tab, each value becomes a row with visitors, registrations, conversion rate and revenue. For an A/B test that is two rows, `A` and `B`, with revenue on each.

Clicking a value pivots the **entire dashboard** to that segment: every KPI, every chart, every other grouping table is then filtered to e.g. `ab_test is B`, the same way the existing filters work. Click `B`, and the whole dashboard becomes "everything that happened to the B cohort", including which campaigns those users originally came from.

## Beyond A/B tests: any segment

The variant is just one attribute. The same `name => value` mechanism groups your analytics by anything you can read off your own models. The user resolver receives the user you already track and returns its current properties:

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

Whenever the package tracks the user, these travel along with the same API request. No extra call, no extra queue job. Now you can ask "which plan converts best?" or "which industry pays the most?" the same way you compared variants.

For property changes that do not coincide with a track the package does on its own (a mid-session plan upgrade, a variant decided before signup), there is an explicit call:

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

## One current value per property

A design decision worth spelling out: a user or visitor can carry as many properties as you like (`ab_test`, `subscription` and `company` all at once), but each property holds exactly **one current value** per user or visitor. Re-tracking `subscription` with a new value overwrites the old one.

That sounds like a limitation, but it is the behavior you want for segmentation. When a user upgrades from `free` to `pro`, their revenue and activity should count under `pro`, not stay frozen under the plan they happened to have at signup. The grouping always reflects the latest known state, the same way your own database does.

If you need the history of an action instead ("how often was the upgrade button clicked?"), that is what [custom events](/docs/how-to-track-a-custom-event.html) are for. Properties are dimensions, events are facts.

## Limits

The practical limits:

- Values must be **scalar** (string, number or boolean). Non-scalar or empty values are ignored.
- Names and values are trimmed to **252 characters**.
- A single track request may include up to **50 properties**.

Custom properties are dimensions, not a data warehouse: a handful of well-chosen properties (`ab_test`, `plan`, `industry`, `signup_source`) beats mirroring half your users table.

## Wrapping up

A/B testing tools are good at telling you which variant gets more clicks. The hard question, which variant makes more money, needs the variant to survive all the way from an anonymous landing-page visit to a payment. Custom properties carry it there: one small resolver class, and every KPI in your dashboard can be split by variant, plan, or anything else you track.

The full reference lives in the [How to track custom properties](/docs/how-to-track-custom-properties.html) docs.
