@component('mail::message')

Hey {{ $user->name }},

Quick update on what we've been shipping lately.

**TL;DR:** Custom Events, custom date range presets, a new $9/mo Starter plan, and Laravel 12 support for the client package.

---

## Custom Events are here

This was one of the most requested features, and it's finally live. You're no longer limited to visitors, registrations, and payments. Now you can track anything: button clicks, feature usage, onboarding steps, plan upgrades, whatever matters to your app.

Custom Events work just like everything else in SimpleStats: server-side, no JS snippet, no ad blocker issues.

**How to enable:** Head to your project settings and toggle Custom Events on. Once enabled, you can start sending events through the client package right away.

@component('mail::button', ['url' => 'https://simplestats.io/docs/how-to-track-a-custom-event.html'])
Check the docs
@endcomponent

---

## Custom Date Range Presets

You can now create your own date range presets in the dashboard. Track "Last 90 days" or "This quarter" with one click. You can also hide any built-in presets you don't need to keep things clean.

---

## New Starter Plan ($9/mo)

We added a more affordable entry point for smaller projects. If you know someone who's been on the fence, this might be a good time to point them our way.

---

## Laravel 12 Support

The SimpleStats client package now fully supports Laravel 12. If you're upgrading your app, the package works out of the box. No config changes needed.

---

## From the blog

We started writing about analytics topics that actually matter for Laravel developers:

- [5 Best Analytics Dashboards for Laravel in 2026](https://simplestats.io/blog/5-best-analytics-dashboards-for-laravel-in-2026) - An honest comparison of what's out there.
- [Web KPIs That Actually Matter](https://simplestats.io/blog/web-kpis-for-laravel-that-actually-matter) - Why page views and bounce rate aren't telling you much, and which metrics you should focus on instead.

---

That's it. Short and sweet. If you have any questions or feature requests, just reply to this email.

Happy tracking,<br>
Zacha

@endcomponent
