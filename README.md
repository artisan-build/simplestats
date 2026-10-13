# SimpleStats

[![Tests](https://github.com/simplestats-io/app/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/simplestats-io/app/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/simplestats-io/app/actions/workflows/static-analysis.yml/badge.svg?branch=main)](https://github.com/simplestats-io/app/actions/workflows/static-analysis.yml)
[![Latest Release](https://img.shields.io/github/v/release/simplestats-io/app)](https://github.com/simplestats-io/app/releases)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

Open source, server-side analytics for Laravel. Self-host it for free, or use the hosted version at [simplestats.io](https://simplestats.io).

![screenshot](https://simplestats.io/images/screenshot.png)

## Introduction

_**SimpleStats**_ is a streamlined analytics tool tailored for **Laravel** applications, transcending mere counts of views and visits. It offers **precise insights** into user origins and behaviors. With default tracking and filtering via [UTM](https://en.wikipedia.org/wiki/UTM_parameters) codes, you gain detailed analysis of **marketing** campaigns, identifying which efforts drive **revenue**. Effortlessly evaluate campaign **ROI**, discover cost-effective user acquisition channels, and pinpoint the most effective performance channels. _SimpleStats_ ensures full **GDPR compliance** and a minimalistic and straightforward installation process.

Follow us on X at [@SimpleStats.io »](https://x.com/SimpleStats_io)

## Requirements

- **PHP:** 8.3 or higher
- **PHP Extensions:** ext-pdo, ext‑zip
- **Laravel:** 11 or higher
- **Database:** PostgreSQL 14 or higher
- **Node.js** 21.6 (lower versions might work)

Plus the default [Laravel requirements](https://laravel.com/docs/12.x/deployment#server-requirements).

## Installation

SimpleStats is a regular Laravel application; it's build on top of Laravel 11 and uses Vue / Inertia.js / Tailwind CSS for the frontend (VILT stack). If you are familiar with Laravel, you should feel right at home.

### Repository

```bash
git clone https://github.com/simplestats-io/app.git ./simplestats

cd simplestats

# use the latest release, main is the rolling state of the hosted version
git checkout $(git describe --tags --abbrev=0)

mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
```

### Dependencies

Next, install the dependencies using [Composer](https://getcomposer.org) and [NPM](https://www.npmjs.com):

```bash
composer install
```

```bash
npm install
```

### Branding and Environment

After that, set up your `.env` file.

Here, you can customize the branding, including the name, logo, and background, to align with your corporate identity (CI). Be sure to review the other environment variables carefully and adjust them as needed.

```bash
cp .env.example .env
```

```bash
php artisan key:generate
```

### Database

Prepare your database and run the migrations:

```bash
php artisan migrate
```

### Storage

Link the storage to the public folder:

```bash
mkdir -p storage/app/public
```

```bash
php artisan storage:link
```

### Assets

Build the assets:

```bash
npm run build
```

### Scheduler

Make sure to run the Laravel [scheduler](https://laravel.com/docs/12.x/scheduling#running-the-scheduler) it performs daily backups, updates GeoIP and executes other important commands.

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Post Installation

After the installation is finished, make sure to create the first user:

```bash
php artisan app:create-new-user {name} {email} {password}
```

**Note:** in the self-hosted version, new users can only register an account with a team invitation.

### Client Package

In your application you want to track, make sure to install the SimpleStats [client package](https://simplestats.io/docs/installation.html) and follow the documentation.

## Deployment

SimpleStats is a standard Laravel application, so deploy it the way you deploy your other Laravel apps (Forge, Cloud, Docker, plain servers, ...). Make sure the queue workers and the scheduler are running. See the [self-hosted docs](https://simplestats.io/docs/self-hosted.html) for queue, scaling and database notes.

To update, check out the next [release](https://github.com/simplestats-io/app/releases) and redeploy. See [Updating](https://simplestats.io/docs/self-hosted.html#updating) for the steps.

`scripts/deploy.sh` and `composer.saas.json` belong to the hosted version at simplestats.io. You don't need them for self-hosting.

## Contributing

Bug fixes, features and docs improvements are welcome. Please read the [contribution guide](CONTRIBUTING.md) first. For larger changes, open an issue to discuss the idea before you start.

## Security

Please don't report security issues in public issues. See [SECURITY.md](SECURITY.md).

## Credits

- [Zacharias Creutznacher](https://github.com/sairahcaz)
- [All Contributors](../../contributors)

## License

GNU Affero General Public License v3.0 or later. Please see [License File](LICENSE) for more information.
