---
title: "Analytics Addon for Statamic: Bring Your Stats Into the Control Panel"
slug: "analytics-addon-for-statamic"
date: "2026-04-29"
excerpt: "If your site runs on Statamic, you probably live inside the Control Panel. Our new Statamic addon brings your SimpleStats data right next to your collections and globals: visitor trends, revenue charts, top sources, and conversion rates, without leaving the Control Panel you already use."
tags:
  - Laravel
  - Statamic
  - Analytics
  - Dashboard
published: true
author_id: 1
---

If you run a Statamic site, you spend a lot of time in the Control Panel. You edit collections, manage entries, tweak globals. But the moment you want to know how a campaign is performing or where today's traffic came from, you switch tabs and open a different tool.

We did exactly that for months. Open the Control Panel, ship a content update, then jump to SimpleStats in another tab to check the numbers. Multiple times a day, every day.

So we built an addon that puts the analytics directly into Statamic.

## What the Addon Does

The SimpleStats Statamic addon adds a full analytics dashboard inside your Control Panel. It connects to your SimpleStats API and renders seven widgets on a dedicated page under the Tools section:

- **Stats Overview:** Six KPI cards showing Visitors, Registrations, Conversion Rate, Net Revenue, ARPU, and ARPV, with trend indicators comparing to a previous period
- **Visitors & Registrations Chart:** Line chart tracking both metrics over time
- **Revenue Chart:** Gross and net revenue trends
- **Top Sources:** Where your visitors come from (utm_source)
- **Top Countries:** Geographic breakdown of your traffic
- **Top Referrers:** Which domains send you the most visitors
- **Entry Pages:** Which pages visitors land on first

Everything updates based on a time range filter (today, last 7 days, last 30 days, this year, etc.) and supports period comparisons so you can spot trends at a glance.

## Setup in Under 5 Minutes

### Step 1: Install the Addon

```bash
composer require simplestats-io/statamic-addon
```

### Step 2: Add Your API Token

Grab your project's API token from your [Projects page](/projects) and add it to your `.env`:

```env
SIMPLESTATS_API_TOKEN=your-api-token-here
```

That's it. A new **SimpleStats** entry shows up under the **Tools** section of the Control Panel navigation. Open it and your analytics dashboard is live.

## Configuration

All configuration is optional. The addon works out of the box with just an API token. If you want to customize the cache duration or point the addon to a self-hosted instance, publish the config:

```bash
php artisan vendor:publish --tag="simplestats-config"
```

This creates `config/simplestats.php` with three settings:

```php
return [
    'api_url' => env('SIMPLESTATS_API_URL', 'https://simplestats.io/api/v1'),
    'api_token' => env('SIMPLESTATS_API_TOKEN'),
    'cache_ttl' => 60,
];
```

### Caching

By default, the addon caches API responses for 60 seconds. This keeps the dashboard snappy without hammering the API on every page load. Multiple widgets sharing the same filters reuse a single cached response. Set `cache_ttl` to `0` to disable caching entirely.

### Self-Hosted Support

If you're running a self-hosted SimpleStats instance, point the addon to your own API via `.env`:

```env
SIMPLESTATS_API_URL=https://stats.yourdomain.com/api/v1
SIMPLESTATS_API_TOKEN=your-api-token-here
```

## Why Add It to the Control Panel?

You can absolutely keep using the SimpleStats web dashboard. It gives you the full picture with more detailed views and filtering. The Statamic addon is for the daily glance.

The difference is friction. When your analytics live inside the tool you already have open, you check them more often. You notice a traffic spike the same morning it happens. You catch a revenue drop before it becomes a trend. You compare campaign performance while you're already editing the landing page that drives it.

It's the same data, just closer to where you work.

## What You Need

- PHP 8.2+
- Statamic 6+
- A SimpleStats API token (grab yours under [Projects](/projects))

The addon handles errors gracefully. If the API is unreachable (network issue, expired token), the widgets show empty states instead of breaking your Control Panel.

## Getting Started

If you're already using SimpleStats, install the addon and you're done. If you're new to SimpleStats, the [installation guide](/docs/installation.html) gets you from zero to tracking in about 10 minutes. The Statamic addon is just one more `composer require` on top of that.

For the full addon documentation, including all configuration options and widget details, check out the [Statamic addon docs](/docs/statamic-addon.html).
