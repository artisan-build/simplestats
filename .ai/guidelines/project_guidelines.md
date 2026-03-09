# SimpleStats Project Guidelines

## Overview

SimpleStats is a **server-side analytics SaaS** built specifically for Laravel applications. It provides precise insights into user origins, behaviors, and revenue attribution by tracking visitors, registrations, logins, and payments. The platform is **GDPR-compliant** and unaffected by ad blockers since all tracking occurs server-side.

### Why Server-Side Tracking?

- Ad blockers can block **30-50%** of client-side analytics data
- Server-side tracking ensures more accurate and comprehensive data collection
- Enhanced data security without relying on third-party cookies
- Full compliance with data protection regulations (GDPR)

## Architecture

### Two-Part System

1. **SimpleStats App** (this repository): The main Laravel SaaS application hosted at https://simplestats.io
   - Dashboard for viewing analytics
   - Team and project management
   - Data import functionality
   - API endpoints for receiving tracking data

2. **Laravel Client Package** (`packages/simplestats-io/laravel-client`): Composer package installed by customers
   - Located at `composer require simplestats-io/laravel-client`
   - Supports Laravel 8, 9, 10, 11, and 12
   - Sends tracking data to the SimpleStats API

### Tech Stack

- **Backend**: Laravel 11 (VILT Stack)
- **Frontend**: Vue 3 / Inertia.js / Tailwind CSS
- **Database**: PostgreSQL or MySQL 8.0+
- **Queue**: Laravel Horizon for background job processing

### Database Strategy

- **SaaS**: PostgreSQL with TimescaleDB extension for time-series optimization
- **Self-hosted**: PostgreSQL (recommended) or MySQL 8.0+
- **Migrations must work on all three**: MySQL, pure PostgreSQL, and PostgreSQL with TimescaleDB

### Deployment Options

1. **SaaS** (recommended): Hosted at https://simplestats.io with automatic updates and dedicated support
2. **Self-hosted**: Full control over data and deployment, requires valid license
   - Setup, maintenance, and troubleshooting are the customer's responsibility
   - Uses `composer.self-hosted.json` for dependencies
   - Requires: PHP 8.2+, Laravel 11+, PostgreSQL 14+ (recommended) or MySQL 8.0+, Node.js 21.6+

## Laravel Boost

This project uses **Laravel Boost**, an MCP server that auto-generates `CLAUDE.md` from the files in `.ai/guidelines/`.

- **Never edit `CLAUDE.md` directly** — it is auto-generated and will be overwritten.
- All project guidelines must be added/edited in `.ai/guidelines/` (e.g., `.ai/guidelines/project_guidelines.md`).
- Laravel Boost provides MCP tools for database queries, Artisan commands, documentation search, tinker, and more — use them.

## Skills

Domain-specific skills are available in `.claude/skills/` and should be activated when working in their domain:

- **`pest-testing`** — Activate when writing or debugging tests, adding assertions, or working with Pest.
- **`inertia-vue-development`** — Activate when creating/editing Vue pages, forms, or navigation with Inertia.
- **`tailwindcss-development`** — Activate when adding or modifying styles, working with Tailwind CSS utilities.

## Performance is Critical

The app must handle **hundreds of thousands of requests** and store massive amounts of data efficiently.

### Performance Guidelines

- Optimize all database queries - use indexes appropriately
- Use eager loading to prevent N+1 queries
- Leverage Laravel's queue system for heavy operations
- Use database-level aggregations where possible
- Cache frequently accessed data appropriately
- ~~Consider read replicas for high-traffic scenarios~~

## Data Model Concepts

### Entities Tracked

1. **Visitors**: Unique visitors identified by a GDPR-compliant hash (IP + User-Agent + daily salt)
2. **Users**: Registered users (linked to customer's User model)
3. **Logins**: User authentication events
4. **Payments**: Financial transactions with gross/net amounts in cents

### Flexible Tracking Configuration

Clients choose their tracking combination during registration. Not all apps need every feature:

| Tracking Type | Description |
|---------------|-------------|
| **Visitors** | Track anonymous visitors with UTMs, location, devices, etc. |
| **Registrations** | Track user signups and logins, including timestamps |
| **Payments** | Track revenue and link it back to visitors or registrations |

Common combinations:
- **Visitors only**: Landing pages, content sites
- **Visitors + Registrations**: Apps without payments
- **Visitors + Payments**: E-commerce without user accounts (guest checkout)
- **All three**: Full-featured SaaS apps

This is stored as a bitmask per project (`TRACKING_USAGE_BITS_*`).

### Tracking Codes (UTM Parameters)

The client package tracks these dimensions:
- `source`: utm_source, ref, referer, referrer
- `medium`: utm_medium, adGroup, adGroupId
- `campaign`: utm_campaign
- `term`: utm_term
- `content`: utm_content

Payments are **always** associated with the user's registration tracking codes for campaign ROI analysis.

## API Design

### Base URL

`https://simplestats.io/api/v1/`

### Rate Limits

Rate limits are defined per plan in `config/spark.php` under `ApiRateLimit`:

| Plan       | Rate Limit |
|------------|------------|
| Free       | 200/min    |
| Starter    | 500/min    |
| Plus       | 1,000/min  |
| Business   | 2,500/min  |
| Enterprise | 5,000/min  |

### Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/stats-visitor` | Track unique visitor |
| POST | `/stats-user` | Track new registration |
| POST | `/stats-login` | Track login event |
| POST | `/stats-payment` | Track payment (amounts in cents) |

### Important API Rules

- All amounts (gross, net) must be in **cents** (e.g., $1 = 100)
- Currency must be **ISO-4217** format (EUR, USD, GBP)
- Time format: `Y-m-d H:i:s P`
- IP and User-Agent are used for geo/device detection but **not stored**

## Client Package Architecture

Located in `packages/simplestats-io/laravel-client/`:

### Key Components

- **SimplestatsClientServiceProvider**: Registers middleware, observers, and event listeners
- **CheckTracking Middleware**: Captures visitor data on first GET request
- **UserObserver**: Tracks user registrations
- **PaymentObserver**: Tracks payments
- **UserLoginListener**: Tracks login events
- **SendApiRequest Job**: Queued API calls with retry logic

### Contracts for Customer Implementation

- `TrackablePerson`: Basic user tracking
- `TrackablePersonWithCondition`: Conditional user tracking (e.g., after email verification)
- `TrackablePayment`: Basic payment tracking
- `TrackablePaymentWithCondition`: Conditional payment tracking (e.g., after status = completed)

### Visitor Hash

```php
// Hash = hash(User-Agent + IP + daily_salt)
// Stored in session: session('simplestats.visitor_hash')
```

## KPIs Provided

The dashboard displays these key performance indicators:

| KPI | Description |
|-----|-------------|
| VISITORS | Unique visitors |
| REG | New registrations |
| CR | Conversion rate (visitors to registrations) |
| NET/GROSS | Revenue metrics |
| ARPU | Average Revenue Per User |
| ARPV | Average Revenue Per Visitor |
| ARPPU | Average Revenue Per Paying User |
| DAU/WAU/MAU | Active users (daily/weekly/monthly) |
| DAPU/WAPU/MAPU | Active paying users |
| REG2PU | Registration to paying user conversion |

## Multi-Tenancy

### Team Structure

- Teams can have multiple projects
- Each project has its own API token
- Team members have roles: Administrator, Editor, Viewer
- Stats can be viewed per-project or aggregated at team level

### Multi-Currency

- Each team selects a default currency
- Foreign currency transactions are auto-converted using daily exchange rates

## Queue Configuration

The client package uses Laravel's defer helper (Laravel 11+) or queues:

```php
'queue' => env('SIMPLESTATS_QUEUE', 'default'),
```

Jobs include automatic retry logic for fault tolerance.

## Security Considerations

- API tokens must be kept secret
- IPs and User-Agents are processed but **never stored**
- Visitor hashes are anonymized (daily salt rotation)
- GDPR-compliant by design

## Coding Conventions

### Helpers

- Prefer Laravel helpers (`Str::`, `Arr::`, `collect()`, etc.) over native PHP functions where a Laravel equivalent exists.

### Confirmation Dialogs

- Never use the browser's native `confirm()` dialog. Always use the `ConfirmationModal` component (`@/Components/ConfirmationModal.vue`) for delete or destructive action confirmations.

### Carbon / Dates

- Always use `CarbonImmutable` instead of mutable `Carbon`. The app registers `Date::use(CarbonImmutable::class)` globally, so `now()` and `Date::parse()` already return immutable instances. When importing Carbon directly, use `use Carbon\CarbonImmutable;` instead of `use Carbon\Carbon;`. Mutable Carbon causes subtle bugs when objects are passed to methods that call mutating operations like `startOfWeek()`, `endOfDay()`, etc.

### Imports

- Always use `use` imports at the top of the file instead of inline fully-qualified class names (e.g., `use App\Models\User;` instead of `\App\Models\User::query()`).

### Controllers & Business Logic

- Controllers should be thin. Keep business logic out of controllers and move it into dedicated service classes (e.g., `App\Services\TrackingService`).
- Controllers should only handle HTTP concerns: receiving the request, delegating to a service, and returning a response.

## Development Guidelines

### Git Workflow

- Do NOT stage changes automatically after making edits
- Do NOT ask if commits should be created - the user will commit when ready
- Only commit when explicitly requested by the user

### When Working on the Main App

- Prioritize query performance for high-volume data
- Test with large datasets
- Consider database partitioning for time-series data
- Ensure API responses are fast (async processing via queues)

### When Working on the Client Package

- Maintain backwards compatibility with Laravel 8-12
- Keep package lightweight (minimal dependencies)
- Never block customer requests - use defer/queues
- Graceful degradation when API is unreachable

## Testing

- Use factories for creating test data
- Test with realistic data volumes
- Verify queue job handling
- Test API rate limiting behavior
- Ensure tracking works across Laravel versions

## Common Tasks

### Adding New KPIs

1. Define the calculation logic
2. Add to aggregation queries
3. Update dashboard components
4. Document in introduction.md

### Modifying API Endpoints

1. Consider backwards compatibility
2. Version new endpoints if breaking changes
3. Update api-documentation.md
4. Update client package if needed

### Client Package Updates

1. Test across all supported Laravel versions
2. Update composer.json version constraints
3. Document changes in CHANGELOG
4. Tag release on GitHub

### Modifying Subscription Plans

When changing plan features or limits in `config/spark.php`:

1. Update the plan configuration in `config/spark.php`
2. Update `docs/plans-and-limits.md` to reflect the changes
3. Update this guidelines file if rate limits change
