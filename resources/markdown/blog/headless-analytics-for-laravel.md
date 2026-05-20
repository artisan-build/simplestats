---
title: "Headless Analytics for Laravel: SPAs, Mobile Apps, Stateless APIs"
slug: "headless-analytics-for-laravel"
date: "2026-05-20"
excerpt: "Until now, the SimpleStats Laravel client relied on the session cookie to remember which visitor a registration, login or payment belongs to. That works for classic Blade, Inertia and Livewire apps, but it falls apart the moment your frontend lives on a separate domain or your API is fully stateless. With the new cache-based tracking storage, the client now covers headless SPAs, mobile apps and JWT/Sanctum API setups."
tags:
  - Laravel
  - Analytics
  - Headless
  - SPA
  - Stateless API
published: true
author_id: 1
---

Most of the SimpleStats marketing talks about Laravel apps in the classic sense: Blade, Inertia, Livewire. Server renders the page, browser hits the server, session cookie carries the visitor across requests. That covers a large chunk of the Laravel world, but not all of it.

The other chunk: SaaS dashboards built as a Vue or React SPA on a separate domain, mobile apps talking to a Laravel API, marketing sites consuming a headless CMS, Sanctum personal access tokens or JWTs replacing cookies entirely. In those setups, the Laravel backend never sees a session, and the previous SimpleStats client silently dropped the visitor context as soon as the next request came in.

The new `v4.0` release of the Laravel client fixes that. You can now choose where the client stores attribution data: the classic Laravel session, or the cache.

## Why the session was the problem

The original middleware does roughly this on the first request:

1. Hash the visitor (IP + user agent + daily salt).
2. Pull UTM parameters out of the URL.
3. Store everything in the session.
4. On every later request (registration, login, payment), read it back from the session and link the event to the original visit.

Step 3 is where headless setups die. If the browser never sends a session cookie back to your API (different domain, stateless auth, mobile app), step 4 always returns nothing. Visitors get tracked, but every later registration looks like it came from `direct` traffic with no UTMs. Campaign ROI becomes impossible to measure.

The fix is conceptually small: instead of binding the attribution to a session, bind it to the visitor hash, and store it in a shared cache that every request can read.

## The new config

`config/simplestats-client.php` gets two new keys, `middleware_groups` and `tracking_storage`:

```php
return [
    // ...
    'middleware_groups' => ['web'],
    'tracking_storage' => 'session', // or 'cache'
];
```

`middleware_groups` controls which Laravel middleware groups the `CheckTracking` middleware is appended to. `tracking_storage` controls where the per-visitor attribution data lives between requests.

Three realistic profiles:

**Server-rendered (default).** Classic Laravel with Blade, Inertia or Livewire. Browser hits your backend directly. Nothing to change.

```php
'middleware_groups' => ['web'],
'tracking_storage' => 'session',
```

**Headless / SPA.** Frontend on a separate domain (Vue, React, Next, mobile app). Backend only ever sees API calls.

```php
'middleware_groups' => ['api'],
'tracking_storage' => 'cache',
```

**Hybrid.** Marketing pages in Blade, dashboard as a SPA, same Laravel app behind both.

```php
'middleware_groups' => ['web', 'api'],
'tracking_storage' => 'cache',
```

## What the frontend needs to send

In a server-rendered app, the middleware already has everything: the visitor's URL, the `Referer` header, the UTM parameters from the query string. In a SPA, the Laravel API only ever sees something like `GET /api/me`, which means:

- `Referer` points at your own frontend domain, not `google.com`.
- The request path is an API route, not the landing page.
- UTM parameters are gone unless the frontend forwards them.

So the SPA has to do one initial call to your API and pass the original context as headers or query parameters. One call. The middleware caches the result for the rest of the day, and every later event (registration, login, payment) is attributed automatically.

```js
const params = new URLSearchParams(location.search)

fetch('https://api.your-app.com/track-init', {
  method: 'GET',
  credentials: 'include',
  headers: {
    'X-Utm-Source': params.get('utm_source') ?? '',
    'X-Utm-Medium': params.get('utm_medium') ?? '',
    'X-Utm-Campaign': params.get('utm_campaign') ?? '',
    'X-Utm-Term': params.get('utm_term') ?? '',
    'X-Utm-Content': params.get('utm_content') ?? '',
    'X-Document-Referer': document.referrer,
    'X-Page': location.pathname,
  },
})
```

On the backend, any GET route that the `CheckTracking` middleware sees will work:

```php
// routes/api.php
Route::get('/track-init', fn () => response()->noContent());
```

That single fire-and-forget call populates the cache for this visitor. The next time the user registers, logs in or pays, the observer reads the cached context using the same `IP + user-agent + day` hash and links the event back to the original campaign.

## How the cache identifies a visitor

The cache key is derived from the visitor hash, the same one used in the session-based flow:

```
hash(User-Agent + IP + daily_salt)
```

That keeps the package GDPR-compliant: no cookies on the user's device, no localStorage identifier, no device fingerprint. The trade-off is the same one Plausible and Fathom make: if a user switches between WiFi and mobile data between two requests, the IP changes, the hash changes, and the same person can count as two visitors for that day.

In practice this affects raw visitor counts, not revenue. Logins and payments are linked to a user ID, and the user's UTM attribution is captured once at registration and reused from there. The only real edge case is a network switch _between_ the landing page and the registration itself, which is rare in normal funnels but worth knowing about.

The alternative (persisting an identifier on the device) would fall under the ePrivacy directive in the EU and require a consent banner, which defeats the point of server-side tracking.

## Cache requirements

The cache has to be shared across all instances behind your load balancer. That means `redis` (recommended) or `memcached`. The `file` driver works on a single host.

`php artisan cache:clear` on every deploy will drop in-flight attribution for the day. If you deploy multiple times a day, consider clearing more selectively.

## What this unlocks

Before this change, headless Laravel users had two options: roll their own attribution layer (cookies on the frontend, custom API endpoint, manual visitor IDs on every event), or accept that all conversions look like `direct` traffic. Neither is great.

With `tracking_storage => 'cache'`, the same `TrackablePerson`, `TrackablePayment` and login event hooks that already work for Blade apps now work for:

- Vue, React or Next frontends on a different domain.
- Mobile apps talking to a Laravel API.
- JWT-authenticated or Sanctum personal-access-token APIs with no session cookie.
- Hybrid setups where the marketing site is server-rendered and the app is a SPA.

Same KPIs (Conversion Rate, ARPV, ARPU, Reg2PU), same campaign attribution, same GDPR posture. The only thing the frontend has to do is send one tracking call on the first page load.

The full setup walkthrough lives in the [Headless, SPA & Stateless Backends](/docs/headless-stateless-spa.html) docs.
