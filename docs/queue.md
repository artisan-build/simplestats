# Queue

To **avoid** the tracking API calls to _SimpleStats_ **blocking** the request and for fault tolerance and retries, we **highly** recommend making use of Laravel's built-in [queue service](https://laravel.com/docs/11.x/queues).

::: warning
Should you still prefer to not use the queue, you can skip this section and head over to the next page of our documentation. But be aware if something went wrong during API communication, you may **lose** important tracking data information!
:::

## Configuration

Here you can define to which queue the tracking **API calls** should be **dispatched**:

```php
//config/simplestats-client.php

    'queue' => env('SIMPLESTATS_QUEUE', 'default'),

```

## Job Monitoring

It's important to know if your queue is **running** and if the API calls are getting dispatched **successfully**. If you want something light-weight, we highly recommend spaties [laravel-failed-job-monitor](https://github.com/spatie/laravel-failed-job-monitor) package, which can notify you via mail or Slack when a queued job fails. If you want something more **robust**, you may use [Horizon](https://laravel.com/docs/11.x/horizon), Laravel's native queue monitoring service!

## Fault Tolerance and Retries

Despite our best efforts to maintain continuous availability, the complex nature of API communications means that **interruptions can occur** for various reasons, ranging from network issues on your end to temporary outages of our service. To mitigate these challenges, we've designed the job that communicates with our API to be **resilient**, automatically retrying failed API calls multiple times with specified intervals between attempts. This strategy helps **safeguard** against data loss during unexpected disruptions in communication.

In the event you suspect data was not captured due to prolonged service interruptions, our [Data Import](data-import.md) feature provides a straightforward way to synchronize the missing information with our system, ensuring your statistics remain complete and accurate.

