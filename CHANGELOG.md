# Changelog

All notable changes to `simplestats-io/app` will be documented in this file.

## v6.3.0 - 2026-10-24

### Upgrade notes

- Contains a migration that recalculates the login and payment counters once. It runs automatically with `php artisan migrate` and takes a few seconds even on large databases.

### Fixed

- Paying Users, Reg-to-Pay, ARPPU and Time to Pay could be too high in projects whose payment ids also occur in another project. The nightly counter check now ranks payments per project, and affected dashboards refresh automatically.

### Improved

- Logins of paying users no longer discard the cached current period, so dashboards with month or year ranges stay fast throughout the day.

**Full Changelog**: https://github.com/simplestats-io/app/compare/v6.2.1...v6.3.0

## v6.2.1 - 2026-10-24

### Security

- Upgrade `laravel/mcp` to 1.1.1 (fixes GHSA-m25c-38v7-94p8). The MCP endpoint now also supports the 2026-07-28 protocol revision, existing MCP clients keep working unchanged.
- Upgrade `livewire/livewire` to 4.4.8 (fixes GHSA-mhf7-xp6r-6qrc).
- Upgrade `vite` to 6.4.5 to fix the `launch-editor` advisories (build tooling only).

### Fixed

- Bump `follow-redirects` to 1.16.1 and `driver.js` to 1.10.1.
- Update `laravel/framework` to 12.70.1 and related dependencies.

### Upgrade notes

No action required. Run `composer install` and `npm ci && npm run build` as usual.

### What's Changed

* Bump driver.js from 1.9.0 to 1.10.1 in the npm-minor-and-patch group by @dependabot[bot] in https://github.com/simplestats-io/app/pull/391
* Bump follow-redirects from 1.16.0 to 1.16.1 by @dependabot[bot] in https://github.com/simplestats-io/app/pull/392

**Full Changelog**: https://github.com/simplestats-io/app/compare/v6.2.0...v6.2.1

## v6.2.0 - 2026-10-19

### Upgrade notes

- Run `php artisan migrate`: a migration adds the missing `check_name` and `check_label` columns to the health check history table.
- No new required environment variables. New optional ones:
  - `CACHE_STORE` (the Laravel 11+ name) is now read; `CACHE_DRIVER` keeps working as a fallback.
  - `BACKUP_ENABLED` (default `true`) turns scheduled backups off where the platform already backs up the database. On Laravel Cloud backups are off by default and target the attached bucket.
  - `SHARED_DISK` (default `local`) selects the disk used to hand data import uploads to the queue worker, for setups where web and worker do not share a filesystem.
  

### Added

- The first user created with `php artisan app:create-new-user` is now an admin, so a fresh self-hosted install can reach the admin panel, Horizon and Pulse right away.
- Trademark guidelines page and a license & trademarks section in the README.
- Horizon and Pulse are linked for admins in the profile dropdown, which is now grouped into sections.

### Fixed

- Scheduled project reports no longer abort when one of the recipients' accounts is deactivated, so all other reports of that run are sent again.
- Project reports and alerts are no longer sent to deactivated accounts.
- Removing a member from a team (or leaving it) now also removes their report and alert subscriptions for that team's projects.
- The Horizon health check is only registered when the queue actually runs on Horizon.
- Data imports work when the web server and queue worker run on separate filesystems.
- Page titles no longer render as "undefined SimpleStats" in builds without a local `.env`.
- The `storage/` skeleton is part of the repository again, so a fresh clone installs without errors.
- Broken API link in the landing page footer; the old API documentation URL now redirects to the ingest API docs.

### What's Changed

* Keep the storage skeleton in the repository by @edgrosvenor in https://github.com/simplestats-io/app/pull/383
* Read the cache store from CACHE_STORE as well as CACHE_DRIVER by @edgrosvenor in https://github.com/simplestats-io/app/pull/384
* Stop page titles rendering as "undefined SimpleStats" by @edgrosvenor in https://github.com/simplestats-io/app/pull/386
* Register the Horizon health check only when the queue runs on Horizon by @edgrosvenor in https://github.com/simplestats-io/app/pull/388
* Let backups default to off where the platform already backs the database up by @edgrosvenor in https://github.com/simplestats-io/app/pull/389
* Add the missing check_name and check_label health history columns by @edgrosvenor in https://github.com/simplestats-io/app/pull/385
* Hand data import uploads to the queue worker on a shared disk by @edgrosvenor in https://github.com/simplestats-io/app/pull/387
* Make the first user created by app:create-new-user an admin by @edgrosvenor in https://github.com/simplestats-io/app/pull/390

### New Contributors

* @edgrosvenor made their first contribution in https://github.com/simplestats-io/app/pull/383

**Full Changelog**: https://github.com/simplestats-io/app/compare/v6.1.1...v6.2.0

## v6.1.1 - 2026-10-15

**Full Changelog**: https://github.com/simplestats-io/app/compare/v6.1.0...v6.1.1

## v6.1.0 - 2026-10-14

### Upgrade notes

- **Backup disks are now configurable via `BACKUP_DISKS`** (comma-separated, default `local`). Previously backups were always written to `local` and `s3`. If you rely on off-server backups on S3, set `BACKUP_DISKS=local,s3` in your `.env` before updating, otherwise backups are only stored on the server. See `docs/self-hosted.md` (Backups).

### Fixes

- Server-side rendered public pages (landing page, auth pages) briefly showed `[object Promise]` before the page loaded, caused by the Vue 3.5.44 update in v6.0.1.

### What's Changed

* Bump ramsey/composer-install from 3 to 4 by @dependabot[bot] in https://github.com/simplestats-io/app/pull/379
* Bump actions/cache from 4 to 6 by @dependabot[bot] in https://github.com/simplestats-io/app/pull/377
* Bump actions/checkout from 5 to 7 by @dependabot[bot] in https://github.com/simplestats-io/app/pull/378
* Bump the npm-minor-and-patch group with 13 updates by @dependabot[bot] in https://github.com/simplestats-io/app/pull/382

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/simplestats-io/app/pull/379

**Full Changelog**: https://github.com/simplestats-io/app/compare/v6.0.1...v6.1.0

## v6.0.1 - 2026-10-13

### Security

Updates dependencies with known vulnerabilities: `maatwebsite/excel` 3.1.70, `phpoffice/phpspreadsheet` 1.30.7, `livewire/livewire` 4.4.7, `phpseclib/phpseclib` 3.0.57, `web-auth/webauthn-lib` 5.3.9 and `vue` 3.5.44, plus several patched npm packages. No configuration changes are required, a regular update (`composer install`, `npm ci`, `npm run build`) is enough.

**Full Changelog**: https://github.com/simplestats-io/app/compare/v6.0.0...v6.0.1

## v6.0.0 - 2026-10-13

First tagged release of the open source version. Self-hosted installations should use tagged releases from now on, see the updating guide: https://simplestats.io/docs/self-hosted.html#updating
