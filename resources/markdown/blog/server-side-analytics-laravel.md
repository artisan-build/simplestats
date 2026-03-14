---
title: "We Built Server-Side Analytics for Laravel Because Ad Blockers Were Eating Our Data"
slug: "server-side-analytics-laravel"
date: "2026-02-15"
excerpt: "30-50% of tracking data gets blocked by ad blockers. Here is why we built server-side analytics for Laravel and what metrics actually matter."
tags:
  - Laravel
  - Analytics
  - Server-Side
published: true
author_id: 1
---

I've been building Laravel apps for years. Like most developers, I added Google Analytics, later switched to Plausible, and assumed I had decent insight into what was happening with my products.

Then I started comparing server logs to my analytics dashboard. The numbers didn't match. Not even close.

Turns out, **30-50% of my tracking data was getting blocked by ad blockers**. Every privacy extension, every browser with built-in tracking protection, every corporate proxy was silently dropping my JavaScript analytics calls. Half my data was fiction.

## The realization that changed everything

I was running a Laravel SaaS and wanted to answer a simple question: *Which marketing campaign actually generates revenue?*

My analytics tool told me Twitter was my best channel because it drove the most traffic. But when I manually traced payments back to acquisition sources, the newsletter (with 6x fewer visitors) was generating **32x more revenue per visitor**. I was optimizing for the wrong channel because my analytics only measured *volume*, not *value*.

That's when I realized two things:

1. Client-side tracking is fundamentally broken for business decisions
2. The metrics most analytics tools show you (page views, bounce rate, session duration) are vanity metrics that don't answer the questions that matter

## What we built

SimpleStats is a server-side analytics platform built exclusively for Laravel. No JavaScript snippets, no cookies, no third-party scripts. All tracking happens on the backend through Laravel's own primitives: middleware for visitors, Eloquent observers for registrations and payments, event listeners for logins.

Because it's server-side, ad blockers can't touch it. 100% of your data gets recorded.

But the bigger insight wasn't just *how* to track. It was *what* to track.

## Why most analytics metrics are useless

Traditional dashboards celebrate page views and bounce rates. They look impressive in screenshots but rarely help you make better decisions. Ask yourself:

- Page views went up 40%: did revenue go up too?
- Session duration increased: are users engaged, or confused?
- Bounce rate dropped: did any of those visitors actually convert?

These metrics describe *traffic*. They don't describe *business performance*.

Here's what we track instead:

**Conversion Rate (CR):** What percentage of visitors actually register? If a campaign drives 10,000 visitors but only 50 sign up, the landing page needs work, not more traffic.

**Revenue Per Visitor (ARPV):** This connects traffic directly to money. You can compare ARPV across campaigns. A campaign with fewer visitors but higher ARPV is almost always better than a high-traffic, low-quality campaign.

**Registration-to-Paying-User (Reg2PU):** If 2% of registrations convert to paying users, you can calculate expected revenue from any acquisition campaign *before* you spend the money.

**Active Users (DAU/WAU/MAU):** A growing user base means nothing if nobody comes back. Active user counts tell you whether your product has retention.

Here's a real-world example of why this matters:

| Campaign | Visitors | Registrations | CR | Revenue | ARPV |
|---|---|---|---|---|---|
| Google Ads | 8,000 | 240 | 3.0% | $4,800 | $0.60 |
| Twitter | 12,000 | 180 | 1.5% | $1,800 | $0.15 |
| Newsletter | 2,000 | 160 | 8.0% | $9,600 | $4.80 |

A traditional analytics tool celebrates Twitter. ARPV tells you the newsletter generates **8x more revenue per visitor**.

## How it works technically

The Laravel integration is intentionally minimal. Install the Composer package, set your API token, and visitor tracking works automatically via middleware.

For user tracking, you implement a single interface on your User model:

```php
class User extends Authenticatable implements TrackablePerson
{
    public function getTrackingTime(): CarbonInterface
    {
        return $this->created_at;
    }
}
```

Login tracking is fully automatic. It hooks into Laravel's built-in `Login` event.

Payment tracking works the same way: implement `TrackablePayment` on your payment model, and the Eloquent observer handles the rest, including multi-currency conversion.

Everything runs server-side. No JavaScript. No cookies. GDPR compliant by design because IPs and user agents are processed for geolocation but never stored. Visitor identity uses a daily-rotating salted hash.

## What we learned

Building SimpleStats taught us a few things:

1. **The analytics industry optimized for the wrong thing.** Most tools got really good at counting page views in creative ways. Very few help you understand which traffic sources generate actual revenue.

2. **Server-side tracking isn't just about ad blockers.** It's about having a single source of truth. When your tracking lives in the same system as your users and payments, attribution becomes trivial instead of impossible.

3. **Laravel's architecture is perfect for this.** Middleware, observers, event listeners: these primitives were designed for exactly this kind of cross-cutting concern. Building analytics on top of them feels natural, not forced.

SimpleStats is available as a SaaS and as a self-hosted option, with a free tier including 5K events/month.
