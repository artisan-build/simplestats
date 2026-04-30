---
title: "Analytics Plugin for Filament: How to Add a Stats Dashboard to Your Admin Panel"
slug: "analytics-plugin-for-filament"
date: "2026-04-12"
excerpt: "If you're already using Filament to manage your Laravel app, switching to a separate analytics dashboard feels like unnecessary friction. Our new Filament plugin brings your SimpleStats data directly into your admin panel: visitor trends, revenue charts, top sources, and conversion rates, all without leaving the page you're already on."
tags:
  - Laravel
  - Filament
  - Analytics
  - Dashboard
published: true
author_id: 1
---

If you're running a Laravel app with Filament, you already have a great admin panel. You manage users, content, orders, whatever your app needs. But when you want to check how your traffic is doing or which campaign is actually driving revenue, you switch tabs, open a different tool, and context-switch out of your workflow.

![SimpleStats analytics dashboard inside the Filament admin panel](/images/screenshot_filament.jpg)

We kept doing this ourselves. Open Filament, manage the app, then open SimpleStats in another tab to check the numbers. Multiple times a day.

So we built a plugin that puts everything in one place.

## What the Plugin Does

The SimpleStats Filament plugin adds a full analytics dashboard directly inside your Filament panel. It connects to your SimpleStats API and renders seven pre-built widgets:

- **Stats Overview:** Six KPI cards showing Visitors, Registrations, Conversion Rate, Net Revenue, ARPU, and ARPV, with trend indicators comparing to a previous period
- **Visitors & Registrations Chart:** Line chart tracking both metrics over time
- **Revenue Chart:** Gross and net revenue trends
- **Top Sources:** Where your visitors come from (utm_source)
- **Top Countries:** Geographic breakdown of your traffic
- **Top Referrers:** Which domains send you the most visitors
- **Entry Pages:** Which pages visitors land on first

Everything updates based on a time range filter (today, last 7 days, last 30 days, this year, etc.) and supports period comparisons so you can spot trends at a glance.

## Setup in Under 5 Minutes

### Step 1: Install the Package

```bash
composer require simplestats-io/filament-plugin
```

### Step 2: Add Your API Token

Grab your project's API token from your [Projects page](/projects) and add it to your `.env`:

```env
SIMPLESTATS_API_TOKEN=your-api-token-here
```

### Step 3: Register the Plugin

In your Filament `PanelProvider`, add the plugin:

```php
use SimpleStatsIo\FilamentPlugin\SimplestatsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... your existing config
        ->plugin(SimplestatsPlugin::make());
}
```

That's it. Visit `/admin/simplestats` (or whatever your panel path is) and your analytics dashboard is live.

## Customization

The plugin is configurable through a fluent API. You can adjust everything from navigation placement to caching behavior:

```php
SimplestatsPlugin::make()
    ->navigationGroup('Analytics')
    ->navigationLabel('Stats')
    ->navigationSort(5)
    ->navigationIcon('heroicon-o-chart-bar-square')
    ->cacheTtl(120)
```

### Caching

By default, the plugin caches API responses for 60 seconds. This keeps your dashboard snappy without hammering the API on every page load. You can adjust the TTL or disable caching entirely by setting it to `0`.

### Self-Hosted Support

If you're running a self-hosted SimpleStats instance, point the plugin to your own API:

```php
SimplestatsPlugin::make()
    ->apiUrl('https://stats.yourdomain.com/api/v1')
    ->apiToken(config('simplestats-filament.api_token'))
```

Or via your `.env`:

```env
SIMPLESTATS_API_URL=https://stats.yourdomain.com/api/v1
```

## Why Not Just Use a Separate Dashboard?

Honestly, both options work. The SimpleStats web dashboard gives you the full picture with more detailed views and filtering. The Filament plugin is for the daily glance.

The difference is friction. When your analytics live inside the tool you already have open, you check them more often. You notice a traffic spike the same morning it happens. You catch a revenue drop before it becomes a trend. You compare campaign performance while you're already looking at user data.

It's the same data, just closer to where you work.

## What You Need

- PHP 8.2+
- Laravel 12+
- Filament v5+
- A SimpleStats API token (grab yours under [Projects](/projects))

The plugin handles errors gracefully. If the API is unreachable (network issue, token expired), the widgets show empty states instead of breaking your admin panel.

## Getting Started

If you're already using SimpleStats, install the plugin and you're done. If you're new to SimpleStats, the [installation guide](/docs/installation.html) gets you from zero to tracking in about 10 minutes. The Filament plugin is just one more `composer require` on top of that.

For the full plugin documentation, including all configuration options and widget details, check out the [Filament plugin docs](/docs/filament-plugin.html).
