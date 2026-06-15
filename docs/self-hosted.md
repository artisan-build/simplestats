# Overview (Self-hosted)

_**SimpleStats**_ is also available as a Self-hosted version.

## SaaS or Self‑hosted?

The **SaaS version** is our recommended solution - no installation required, automatic updates, and dedicated support. It's ideal if you want a seamless experience and quick results.

The **Self‑hosted version** gives you total control over your data and deployment. It's great for teams with technical expertise, but keep in mind that setup, maintenance, and troubleshooting are your responsibility. It doesn't include dedicated support.

We recommend the SaaS version for anyone looking for a smooth, worry‑free analytics experience.

## Requirements

_**SimpleStats**_ Self-hosted requires:

- **PHP:** 8.2 or higher
- **Laravel:** 11 or higher
- **Database:** PostgreSQL 14 or higher
- **PHP Extensions:** ext-pdo, ext‑zip
- **Node.js** 21.6 (lower versions might work)

Plus the default [Laravel requirements](https://laravel.com/docs/12.x/deployment#server-requirements).

## Architecture

SimpleStats self-hosted is a standard Laravel application. The ingest API is intentionally thin: every endpoint validates the payload and immediately dispatches a queued job, the actual database write happens in the queue worker. This separation keeps request latency low and lets you scale ingestion throughput independently of web capacity. Beyond that, deployment is up to you, run it however you run other Laravel apps.

## Horizontal scaling

The ingest API is stateless and follows standard Laravel scaling patterns: multiple web replicas and queue workers behind the same database work out of the box, with no special configuration. Race-safe writes are guaranteed at the database level, so concurrent workers across replicas do not produce duplicates or lost updates.

## Queue workers

All ingest endpoints push a job onto the `ingest` queue. The job code is queue-driver agnostic and works with any driver Laravel supports: Redis, Amazon SQS, database, Beanstalkd, etc. Pick the driver that fits your stack via the standard `QUEUE_CONNECTION` env var.

### With Laravel Horizon (Redis driver)

When running on Redis, we ship a Horizon configuration with three supervisor groups (`ingest`, `default`, `low`). The relevant supervisor settings are configurable via environment variables:

| Variable | Default | Purpose |
|---|---|---|
| `HORIZON_INGEST_MIN_PROCESSES` | `3` | Minimum number of workers always running on the ingest queue. |
| `HORIZON_INGEST_MAX_PROCESSES` | `10` | Maximum number of workers Horizon may scale up to under load. |
| `HORIZON_DEFAULT_MIN_PROCESSES` | `2` | Minimum workers for the default (non-ingest) queue. |
| `HORIZON_DEFAULT_MAX_PROCESSES` | `5` | Maximum workers for the default queue. |
| `HORIZON_LOW_MIN_PROCESSES` | `1` | Minimum workers for the low-priority queue. |
| `HORIZON_LOW_MAX_PROCESSES` | `1` | Maximum workers for the low-priority queue. |

The defaults are a sensible starting point. Right-sizing depends on your expected events per day and your database's write capacity. Set conservative values, watch the Horizon dashboard for wait time on `redis:ingest`, and bump `HORIZON_INGEST_MAX_PROCESSES` if jobs queue up.

### With other drivers (SQS, database, etc.)

Horizon is Redis-only, so the env vars above do not apply when you pick a different driver. You manage worker processes yourself via your platform (Supervisor, systemd, ECS task definitions, Kubernetes deployments, ...) by running `php artisan queue:work <driver> --queue=ingest,default,low` with the desired concurrency.

A few things worth keeping in mind if you go this route:

- The ingest job timeout is 60 seconds with a 90 second retry-after window. Make sure your driver's visibility timeout (for SQS) or job reserve time is at least as long, otherwise jobs may be redelivered while still being processed.
- Scale your worker count based on observed queue depth and job processing latency, the same way you would for any Laravel queue worker.

## Database growth

Stats rows are small (a few hundred bytes including indexes). For typical use cases PostgreSQL handles tens of millions of rows on these tables without trouble on commodity hardware.

## Integration in non-classic Laravel setups

If you run a headless SPA in front of your Laravel API, or use stateless auth (JWT, Bearer tokens, Sanctum personal access tokens), the frontend needs to forward UTMs to the API explicitly. See [Headless & SPA Backends](/headless-stateless-spa).

## License

To use _**SimpleStats**_, a valid license is required.

Please get in touch with our sales team to learn more:
<a href="/#self-hosted" target="_blank">Contact Sales</a>

