---
title: "SimpleStats vs PostHog: Which One Fits Your Laravel App?"
slug: "simplestats-vs-posthog"
date: "2026-10-14"
excerpt: "PostHog is an all-in-one product platform with session replays, feature flags and surveys. SimpleStats does one thing: it shows which campaigns bring you signups and revenue, built for Laravel and set up in minutes. Here's how they compare and when to pick which."
tags:
  - Laravel
  - Analytics
  - Attribution
  - Comparison
published: true
author_id: 1
---

"How is SimpleStats different from PostHog?" is one of the questions we hear most. Fair question: both are open source, both can be self-hosted, and both can track events from a Laravel app.

The short answer: **PostHog is a toolbox for product teams. SimpleStats answers one question really well: which channels and campaigns actually bring you users and revenue?**

Let's look at what that means in practice.

---

## PostHog in a Nutshell

[PostHog](https://posthog.com) is an all-in-one product platform. Next to product analytics it ships session replays, feature flags, experiments, surveys and a lot more. It's built for product teams who want to dig into in-app behavior: which button gets clicked, where users drop off in a flow, what a specific session looked like.

It's mostly client-side: you add a JavaScript snippet to your frontend, and there's a generic PHP SDK to send events from your backend.

If that's what you need, PostHog is a great tool.

## SimpleStats in a Nutshell

[SimpleStats](/) is focused on the full funnel from **first visit to revenue**. It tells you where your visitors come from, how many of them sign up, how many of them pay, and how much each campaign is worth to you.

It's built exclusively for Laravel and tracks everything server-side through a native Composer package.

---

## 1. Setup: Minutes Instead of a Project

With SimpleStats you install the client package:

```bash
composer require simplestats-io/laravel-client
```

That alone already tracks your visitors, including their UTM codes and referrer. Logins are picked up automatically from Laravel's `Login` event. To track registrations, add one contract to your `User` model:

```php
use SimpleStatsIo\LaravelClient\Contracts\TrackablePerson;

class User extends Authenticatable implements TrackablePerson
{
    public function getTrackingTime(): CarbonInterface
    {
        return $this->created_at;
    }
}
```

Revenue works the same way: implement one more contract on your payment model, and every new payment is sent to SimpleStats. That also takes just a few minutes, see the [payment docs](/docs/how-to-track-a-new-payment.html).

With PostHog you can get to a similar place, but you build it yourself: send a capture call for every signup and payment, identify users so their anonymous visits get merged with their account, connect your payment data, and then build the funnels and reports on top. Doable, but it's a project, not a coffee break.

## 2. Attribution Out of the Box

This is the core difference. In SimpleStats, **every registration and every payment is linked back to the visitor's original UTM codes and referrer.** No funnels to build, no users to identify, no reports to set up.

That means you see, per source, campaign or referrer:

- visitors, registrations and conversion rate
- the revenue each of them brought in
- ROAS, CAC and net profit per campaign once you add your ad spend

So the question *"How much revenue did last month's Google Ads campaign actually generate?"* is one click away. Read more about it in [Marketing ROI for Laravel](/blog/marketing-roi-for-laravel).

## 3. Server-Side: No Ad Blockers, No Cookie Banner

Ad blockers can hide up to 30 to 50% of client-side analytics data. With browser-based tools you can work around that with a reverse proxy, but it's one more thing to set up.

SimpleStats tracks on your server, so there's nothing for an ad blocker to block. It's also GDPR compliant by design: visitors are counted with a daily rotating hash instead of a cookie, so no consent banner is needed, and IPs and user agents are never stored. More on that in [Server-Side Analytics for Laravel](/blog/server-side-analytics-laravel).

## 4. A/B Tests, Measured All the Way to Revenue

PostHog has a full experimentation suite: it assigns variants, splits traffic and does the statistics.

SimpleStats takes a simpler approach: **you assign the variant, SimpleStats measures it.** You tag a visitor with their variant via custom properties, and that tag is carried over to their registration. So you don't just see which variant got more visitors, but which one brought more registrations and more revenue. See [A/B Testing for Laravel](/blog/ab-testing-for-laravel) for the details.

## 5. A Dashboard With the KPIs That Matter

PostHog gives you building blocks: you define events, insights and dashboards yourself. That's powerful, but you need to know what to build.

SimpleStats gives you one dashboard with the KPIs a SaaS or online business actually needs, ready on day one: conversion rate, revenue, ARPU, active users, MRR, retention and more. And if you'd rather ask than click, the [AI assistant and MCP server](/blog/ask-your-laravel-analytics-anything-ai-assistant-and-mcp-server) answer questions about your data directly.

---

## At a Glance

| | SimpleStats | PostHog |
|---|---|---|
| **Focus** | Marketing attribution, from visit to revenue | Product analytics and product tooling |
| **Built for** | Laravel (plus a plain PHP client) | Any stack |
| **Tracking** | Server-side | Mostly client-side, server SDKs available |
| **Signups, logins, payments** | Logins automatic, signups and payments via one contract each | Instrumented manually |
| **UTM to revenue attribution** | Out of the box | Build it yourself |
| **Ad blocker resistant** | Yes | With a reverse proxy |
| **A/B tests** | Measured to revenue, you assign the variant | Full experimentation suite |
| **Session replay, feature flags, surveys** | No | Yes |
| **Open source, self-hostable** | Yes (AGPL-3.0) | Yes |

---

## So, Which One Should You Pick?

**Pick PostHog** if you want to understand in-app behavior in detail: session replays, feature flags, heatmaps, surveys. It's the better fit for product teams who want one platform for all of that.

**Pick SimpleStats** if you run a Laravel app and want to know which marketing actually pays off, without spending days on instrumentation. That's exactly what it's built for.

And they don't exclude each other: you can use PostHog for product insights and SimpleStats for attribution and revenue.

[Start for free](/register) and see which of your campaigns actually make money.
