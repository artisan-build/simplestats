# Changelog

All notable changes to `simplestats-io/app` will be documented in this file.

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
