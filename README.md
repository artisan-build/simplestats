# SimpleStats.io

This is the official SimpleStats.io application repository!

![screenshot](https://simplestats.io/images/screenshot.png)

## Introduction

_**SimpleStats**_ is a streamlined analytics tool tailored for **Laravel** applications, transcending mere counts of views and visits. It offers **precise insights** into user origins and behaviors. With default tracking and filtering via [UTM](https://en.wikipedia.org/wiki/UTM_parameters) codes, you gain detailed analysis of **marketing** campaigns, identifying which efforts drive **revenue**. Effortlessly evaluate campaign **ROI**, discover cost-effective user acquisition channels, and pinpoint the most effective performance channels. _SimpleStats_ ensures full **GDPR compliance** and a minimalistic and straightforward installation process.

Follow us on X at [@SimpleStats.io »](https://x.com/SimpleStats_io)

## Requirements

* PHP 8.2 or higher
* ext-zip
* MySQL 8.0 or higher
* Node.js 21.6 or higher (might also work with a lower version)

And the default [Laravel requirements](https://laravel.com/docs/11.x/deployment#server-requirements).

## Installation

SimpleStats is a regular Laravel application; it's build on top of Laravel 11 and uses Vue / Inertia.js / Tailwind CSS for the frontend (VILT stack). If you are familiar with Laravel, you should feel right at home.

```bash
mkdir simplestats

git clone git@github.com:simplestats-io/app.git ./simplestats

cd simplestats
```

Next, install the dependencies using [Composer](https://getcomposer.org) and [NPM](https://www.npmjs.com):

```bash
composer install

npm install
```

After that, set up your `.env` file.

Here, you can customize the branding, including the name, logo, and background, to align with your corporate identity (CI). Be sure to review the other environment variables carefully and adjust them as needed.

```bash
cp .env.example .env

php artisan key:generate
```

Prepare your database and run the migrations:

```bash
php artisan migrate
```

Link the storage to the public folder:

```bash
php artisan storage:link
```

Build the assets:

```bash
npm run build
```

Run the scheduler:

Make sure to run the Laravel [scheduler](https://laravel.com/docs/11.x/scheduling#running-the-scheduler) it performs daily backups, updates GeoIP and executes other important commands.

### Post Installation

After the installation is finished, make sure to create the first user:

```bash
php artisan app:create-new-user {name} {email} {password}
```

**Note:** in the self-hosted version, new users can only register an account with a team invitation.

### Client Package

In your application you want to track, make sure to install the SimpleStats [client package](https://github.com/simplestats-io/laravel-client) and follow the documentation.

## Contributing

If you have feature ideas or bug fixes, feel free to create a pull request. For larger changes, please discuss them with me first. You can email your idea to zacharias@laracraft.tech or create a new issue describing it in detail.

## Credits

- [Zacharias Creutznacher](https://github.com/sairahcaz)
- [All Contributors](../../contributors)

## License

GNU Affero General Public License v3.0 or later. Please see [License File](LICENSE) for more information.
