# API Communication

Make sure you have an TOKEN set in you .env file (link zu installation wo man das token setzt?)

To avoid the tracking API calls block the whole request and for fault tolerance,
we highly recommend to use Laravel's built-in queue-system. Here you can define
to which queue the tracking API calls should be dispatched and handled by.

## Fault Tolerance

## Retries

## Job minitor
like Horizon or Spatie Job Monitor
We highliy recommend to put it to the queue for failure tolerance and performance (does not block the request)!


Get your queue up and running: Laravel Docs Link
