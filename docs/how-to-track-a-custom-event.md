# How to track a custom event

Custom events let you track any user action in your application, such as button clicks, plan upgrades, feature usage, or anything else that matters to your business.

::: info
Custom event tracking requires **version 3.1+** of the _SimpleStats_ [client package](https://github.com/simplestats-io/laravel-client).
:::

## Enabling Custom Events

Make sure the **Custom Events** tracking bit is enabled for your project. You can toggle this in the project settings inside the app.

## Tracking an Event

Use the `SimplestatsClient` facade to track a custom event anywhere in your application:

```php
use SimpleStatsIo\LaravelClient\Facades\SimplestatsClient;

SimplestatsClient::trackCustomEvent(
    id: 'evt-' . Str::uuid(),
    name: 'Button Clicked',
    user: $user,
);
```

**Parameters:**

| Parameter  | Type               | Description                                                                                     |
|------------|--------------------|-------------------------------------------------------------------------------------------------|
| **id**     | _string_           | A unique identifier for this event. Must be unique per project.                                 |
| **name**   | _string_           | The event name (e.g. "Button Clicked", "Plan Upgraded"). Events with the same name are grouped. |
| **user**   | _TrackablePerson_  | The user or visitor associated with the event.                                                  |

### With a User

If the event is triggered by an authenticated user, pass the user model directly:

```php
SimplestatsClient::trackCustomEvent(
    id: 'evt-' . Str::uuid(),
    name: 'Plan Upgraded',
    user: auth()->user(),
);
```

### With a Visitor

If no user is available (e.g. a visitor clicking a CTA before signing up), you can pass a `Visitor` object using the visitor hash stored in the session:

```php
use SimpleStatsIo\LaravelClient\Visitor;

SimplestatsClient::trackCustomEvent(
    id: 'evt-' . Str::uuid(),
    name: 'CTA Clicked',
    user: new Visitor(session('simplestats.visitor_hash')),
);
```

## API

If you're not using the Laravel client package, you can track custom events directly via the API. See the [Create Custom Event](api-documentation.md#create-custom-event) endpoint in the API documentation.
