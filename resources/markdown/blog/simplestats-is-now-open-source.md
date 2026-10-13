---
title: "SimpleStats Is Now Open Source"
slug: "simplestats-is-now-open-source"
date: "2026-10-12"
excerpt: "The complete SimpleStats app, the same code that runs simplestats.io, is now open source under the AGPL-3.0. Here's why we did it, what it means for your data, and what stays the same."
tags:
  - Laravel
  - Open Source
  - Analytics
  - Privacy
published: false
author_id: 1
---

Today I'm happy to share some big news: **SimpleStats is now open source.** The complete app, the same code that runs simplestats.io, is public on [GitHub](https://github.com/simplestats-io/app) under the AGPL-3.0 license.

## Why we're doing this

**Analytics is about trust.** SimpleStats sees your registrations, logins and payments. That's exactly what makes it useful, because it connects every euro of revenue back to the campaign that earned it. But it's also data you should be able to inspect. With the code in the open, you don't have to take our word for how your data is handled. You can read it.

**Better software, together.** SimpleStats is built for Laravel developers, and Laravel developers are exactly the people who know what their analytics should do. Opening the code means you can report a bug with a pointer to the exact line, suggest the metric you're missing, or send a pull request.

**No lock-in.** Your analytics shouldn't depend on a black box. If you ever need to, you can run the same code yourself.

## What this means for you

If you're using SimpleStats today: **nothing changes.** Your account, your data and your plan stay exactly as they are. The hosted version at [simplestats.io](https://simplestats.io) is still the easiest way to get reliable numbers: no servers to run, automatic updates, daily backups and support. The free plan stays too, with 5K events per month.

What's new is that you can now look under the hood: how visitors are counted, how bots are filtered, how revenue is attributed to campaigns. All of it is public.

## Self-hosting

Some teams can't send user and revenue data to a third party, because of company policy, their customers or regulation. For them, SimpleStats can now run on their own infrastructure, free of charge and with the same features.

It's a regular Laravel application (Laravel 12, Inertia + Vue, PostgreSQL), so if you've run a Laravel app in production before, you'll feel right at home. Keep in mind that you're then responsible for the servers, queue workers, updates, backups and security yourself. The [self-hosted docs](/docs/self-hosted.html) compare both options side by side, and the [README](https://github.com/simplestats-io/app#installation) has the installation steps.

## A quick word on the license

SimpleStats uses the [GNU AGPL-3.0](https://github.com/simplestats-io/app/blob/main/LICENSE). In practice:

- You can use and modify SimpleStats for free, also commercially.
- If you modify SimpleStats and offer it to others as a network service, you have to publish your changes under the same license.

The AGPL keeps SimpleStats open: improvements flow back to everyone instead of disappearing into a closed fork.

The client packages for [Laravel](https://github.com/simplestats-io/laravel-client) and [plain PHP](https://github.com/simplestats-io/php-client), the [Filament plugin](https://github.com/simplestats-io/filament-plugin) and the [Statamic addon](https://github.com/simplestats-io/statamic-addon) were already open source.

## Come build with us

Issues, ideas and pull requests are welcome on [GitHub](https://github.com/simplestats-io/app). Have a look at the [contribution guide](https://github.com/simplestats-io/app/blob/main/CONTRIBUTING.md) before you start on something bigger, and open an issue first so we can agree on the approach.

And if you like what we're building, a star on GitHub helps more Laravel developers find SimpleStats.

Not using SimpleStats yet? [Start for free](/register) and see which of your campaigns actually make money.
