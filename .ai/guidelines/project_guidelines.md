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
   - Supports Laravel 11, 12, and 13
   - Sends tracking data to the SimpleStats API

### Tech Stack

- **Backend**: Laravel 12 (VILT Stack)
- **Frontend**: Vue 3 / Inertia.js / Tailwind CSS
- **Database**: PostgreSQL 14+
- **Queue**: Laravel Horizon for background job processing

### Database Strategy

- **SaaS**: PostgreSQL
- **Self-hosted**: PostgreSQL

### Migrations: No `down()` / No Rollbacks

- **We do not use `down()` methods.** Migrations are forward-only. Never add a `down()` method to a migration; rolling back is not part of our workflow (`migrate:rollback` is unused).

### Deployment Options

1. **SaaS** (recommended): Hosted at https://simplestats.io with automatic updates and dedicated support
2. **Self-hosted**: Full control over data and deployment, requires valid license
   - Setup, maintenance, and troubleshooting are the customer's responsibility
   - Uses `composer.self-hosted.json` for dependencies
   - Requires: PHP 8.2+, Laravel 12+, PostgreSQL 14+, Node.js 21.6+

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
- `source`: utm_source, ref
- `medium`: utm_medium, adGroup, adGroupId
- `campaign`: utm_campaign
- `term`: utm_term
- `content`: utm_content

The `tracking_codes` config is the source of truth for the `utm_*`/source params only. The `referer` is the real document referrer and is handled separately by `CheckTracking::getReferer()`: it comes from the HTTP `Referer` header (or an explicitly forwarded `document.referrer` via the `document_referer` param / `X-Document-Referer` header for headless/SPA setups). URL query params like `?referer=`/`?referrer=` are not tracked at all. `ref` stays a `source` alias because it is often a campaign tag (e.g. `?ref=newsletter`); the app backend still maps a domain-like `source` (one containing a dot) to a referer for backwards compatibility with older clients. Neither the client nor the backend ever records the app's own domain as a source or referer.

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
| Scale      | 3,000/min  |
| Premium    | 3,500/min  |
| Ultimate   | 4,000/min  |
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

### SimplestatsClient Stays Orchestrator-Only

`SimplestatsClient` must remain a thin orchestrator: it builds payloads, fires events, and dispatches the queued API calls. Any non-trivial logic (e.g. invoking the customer's custom properties resolvers) belongs in small dedicated service classes under `src/Services/` (see `CustomPropertiesResolver`).

### Keep Client Config in Sync (Three Places)

Whenever the client package config changes (a new key, a changed default, an updated comment block), the same change must be applied in **all three** of these places, they are not generated from one another:

1. `packages/simplestats-io/laravel-client/config/simplestats-client.php` (the actual published config)
2. `packages/simplestats-io/laravel-client/README.md` (the config block in the README)
3. `docs/installation.md` (the full config listing under "Configuration")

Keep the comment blocks and defaults identical across all three so customers see the same thing wherever they look.

### Contracts for Customer Implementation

- `TrackablePerson`: Basic user tracking
- `TrackablePersonWithCondition`: Conditional user tracking (e.g., after email verification)
- `TrackablePayment`: Basic payment tracking
- `TrackablePaymentWithCondition`: Conditional payment tracking (e.g., after status = completed)
- `ResolvesUserCustomProperties` / `ResolvesVisitorCustomProperties`: Custom properties resolvers, registered in the `custom_properties_resolvers` config. Resolver failures must never break the host app (report + skip), a misconfigured resolver class (missing contract) is treated the same way.

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

### Team Switching Redirect

`CurrentTeamController::update()` handles the redirect after a team switch. URLs with team-scoped IDs must not redirect back to the same path, because the ID belongs to the old team. Current rules:

- `/teams/{id}...` redirects to `/teams/{newId}...`
- `/setup/...` redirects to `RouteServiceProvider::HOME`
- `/{entity}/{id}...` (default) strips the ID and redirects to `/{entity}`
- All other paths redirect back unchanged (safe, since `HasTeamScope` re-filters automatically)

### Multi-Currency

- Each team selects a default currency
- Foreign currency transactions are auto-converted using daily exchange rates

### Team Context Resolution

The app uses `AppHelper::getTeamId()` as the single source of truth for resolving the current team ID. It checks two sources in order:

1. `Context::get('team_id')` (set by `SetTeamContext` middleware via `AppHelper::setTeamContextFromAuth()`)
2. `auth()->user()?->team_id ?? auth()->user()?->current_team_id` (fallback for CLI/job contexts)

This matters because the app runs in two different contexts:

- **HTTP requests**: The `SetTeamContext` middleware sets `Context::add('team_id', ...)` from the authenticated user. Both Context and auth are available.
- **CLI / Queued jobs**: No middleware runs. Code that needs auth must call `auth()->setUser(...)` manually. Context is only available if explicitly set.

#### Rules for Team Context

- **Reading team ID**: Always use `AppHelper::getTeamId()`. Never call `Context::get('team_id')` directly.
- **In jobs/commands that need team-scoped data**: Call `auth()->setUser($user)` before any team-scoped operations. This enables both `AppHelper::getTeamId()` (via auth fallback) and `AppHelper::ensureTeamTimezone()` (which requires `auth()->user()->team->timezone`).
- **When the target team differs from the user's `current_team_id`**: Always set `Context::add('team_id', $targetTeamId)` BEFORE `auth()->setUser($user)`. Relying on the auth fallback (`current_team_id`) is unsafe whenever a user belongs to multiple teams: the user may have switched their selected team in the frontend, so `current_team_id` is not guaranteed to match the team owning the data being processed (e.g. an invited collaborator on someone else's team). Required in: scheduled reports, queued data imports, alert notifications, and anywhere a user is acting on data of a team they did not create. Without this, `HasTeamScope` filters by the wrong `team_id` and queries silently return empty results. Reference fixes: `ProjectReportNotification::toMail()`, `ProcessDataImportJob::handle()`.
- **`HasTeamScope` trait**: Uses `AppHelper::getTeamId()` for global scopes and saving hooks. When team ID is null, the scope is skipped (no filtering).
- **`AppHelper::ensureTeamTimezone()`**: Relies on `auth()->user()->team->timezone`. Only called when a team is resolved, so auth must be set beforehand.

#### Team Timezone (`ensureTeamTimezone`)

Dashboards aggregate in the team's local timezone, so `AppHelper::ensureTeamTimezone()` switches the timezone **globally** for the running process (`Config::set('app.timezone', ...)` plus `date_default_timezone_set(...)`). It is triggered from `HasTeamScope` (global scope and saving hooks), which means touching almost any team-scoped model can flip the default timezone mid-request, and in queue workers the switched timezone persists into everything that runs afterwards.

Consequences when working with datetimes:

- `now()` and parsing of timezone-naive datetime strings are context-dependent. Never assume the default timezone is UTC.
- The stats `time` columns are stored timezone-naive. When a stored time must be compared or written independently of team context (e.g. the out-of-order guard in `HasCustomProperties::recordCustomProperties()`), store and compare explicitly in UTC: write via `$time->utc()->format('Y-m-d H:i:s')`, read via `CarbonImmutable::parse($value, 'UTC')`.
- Do **not** add Eloquent `datetime` casts to these naive time columns. The cast re-parses the stored string in the *currently active* default timezone, so the same row reads as a different instant depending on which team context ran last (this caused an exactly-one-timezone-offset bug in the custom properties time guard).
- Incoming API `time` values are normalised to UTC by `BaseStatsValidator::prepareForValidation()` before they reach jobs.

#### Key files

- `App\Helpers\AppHelper` - `getTeamId()`, `setTeamContextFromAuth()`, `ensureTeamTimezone()`
- `App\Http\Middleware\SetTeamContext` - HTTP middleware that sets Context from auth
- `App\Concerns\HasTeamScope` - Eloquent trait for automatic team scoping

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

### No Magic Strings for App Identity

- Never hardcode "SimpleStats" or similar brand names. Use `config('app.name')` instead so values stay consistent across environments and rebranding is painless.

### Enums for Fixed Value Sets

- When a feature has a fixed set of types/states (e.g., feedback types, import types, notification frequencies), define a PHP `enum` in `app/Enums/` backed by `string`.
- Use `LaracraftTech\LaravelUsefulAdditions\Traits\UsefulEnums` trait.
- Add a `title(): string` method for display labels and a `static toOptions(): array` method returning `[['id' => ..., 'name' => ...], ...]` for frontend select inputs.
- Use `Rule::enum(MyEnum::class)` (via `Illuminate\Validation\Rule`) in FormRequest validation instead of `new Enum()` or `Rule::in([...])`. This provides a fluent API with `->only()` and `->except()`.
- See `app/Enums/UserNotificationFrequency.php` as a reference implementation.

### Confirmation Dialogs

- Never use the browser's native `confirm()` dialog. Always use the `ConfirmationModal` component (`@/Components/ConfirmationModal.vue`) for delete or destructive action confirmations.

### Carbon / Dates

- Always use `CarbonImmutable` instead of mutable `Carbon`. The app registers `Date::use(CarbonImmutable::class)` globally, so `now()` and `Date::parse()` already return immutable instances. When importing Carbon directly, use `use Carbon\CarbonImmutable;` instead of `use Carbon\Carbon;`. Mutable Carbon causes subtle bugs when objects are passed to methods that call mutating operations like `startOfWeek()`, `endOfDay()`, etc.

### Translations (i18n)

- Only public (non-authenticated) pages are translated: Landing Page, Imprint, Auth pages. These use `$t()` in Vue templates and keys in `lang/{locale}.json`.
- In-app pages (behind auth) are English-only. Do not wrap in-app strings with `$t()` or add them to language files.
- Translations are managed via `php artisan app:translate` which uses DeepL. See `app/Console/Commands/TranslateLanguageFiles.php`.

### Config Files Must Be Serializable

- Never put closures (or object instances) into files under `config/`. Production deploys run `php artisan config:cache` (via `optimize`), which serializes the whole config with `var_export()`. Closures fail with `Call to undefined method Closure::__set_state()` and break the deployment, while it works fine locally where the config is not cached.
- When a config value needs callable behavior (e.g. Sentry's `before_send`), reference a static method as an array callable instead: `'before_send' => [\App\Sentry\BeforeSend::class, 'handle']`. An array of strings is serializable and Sentry accepts it via `is_callable()`. See `App\Sentry\BeforeSend`.
- After changing config that holds dynamic values, verify with `php artisan config:cache` (then `php artisan config:clear`) before considering the change done.

### Imports

- Always use `use` imports at the top of the file instead of inline fully-qualified class names (e.g., `use App\Models\User;` instead of `\App\Models\User::query()`).

### Comments

- Only write comments that add real value. A comment must explain a non-obvious **why**, a gotcha, or critical context that the code itself cannot convey.
- Never write comments that merely restate what the code does (e.g. `// loop over users` above a `foreach`). Delete them on sight, including in code you are editing.
- Do not add PHPDoc blocks just to describe a method/property whose name and signature already say everything. Skip boilerplate `@param`/`@return` that only repeats the type hints. PHPDoc is still expected where it carries extra information (array shapes, generics, thrown exceptions).
- When in doubt, leave the comment out. The bar is "would a competent reader be confused or surprised without it?"

### Controllers & Business Logic

- Controllers should be thin. Keep business logic out of controllers and move it into dedicated service classes (e.g., `App\Services\TrackingService`).
- Controllers should only handle HTTP concerns: receiving the request, delegating to a service, and returning a response.
- **Controllers must not contain private helper methods.** A private method on a controller is a sign that business logic leaked in. Controller methods should be public route actions only. Extract the helper into a service (for domain/query logic) or a `DataProvider` (for response assembly, see `App\Http\DataProviders\StatsIndexDataProvider`). Same rule for non-action `protected` helpers.

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

## Blog

- Blog posts are managed as markdown files with YAML front matter in `resources/markdown/blog/`, using the `jcfrane/laravel-md-blog` package.
- Posts are version-controlled in Git and require no database. The `MdBlog` facade provides querying (all, find, latest, whereTag, etc.).
- Author information is stored as `author_id` in the front matter `meta` field and resolved to a User model in the controller.

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
