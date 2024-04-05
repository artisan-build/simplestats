# Tracking Codes

In your [published](installation.html#configuration) _SimpleStats_ config file, you can set your tracking parameters.

We already set some classical defaults for you, but you're free to completely change them to your needs. 

## Configuration

```php
//config/simplestats-client.php

    'tracking_codes' => [
        'source' => ['utm_source', 'ref', 'referer', 'referrer'],
        'medium' => ['utm_medium', 'adGroup', 'adGroupId'],
        'campaign' => ['utm_campaign'],
        'term' => ['utm_term'],
        'content' => ['utm_content'],
    ],

```

The **tracking_codes** array is structured hierarchically, grouping parameters by tracking dimensions such as _source_, _medium_, _campaign_, _term_, and _content_. This organization allows for detailed filtering of your data by categorizing the origins and behaviors of the users, offering insights into the performance of your marketing efforts.

:::warning
Only the tracking params listed here are getting tracked and associated with a user registration!
:::

::: danger
The keys must be named _source, medium, campaign, term and content_!
:::

## Valid Tracking URLs

So regarding the current config above, these URL params would be tracked and associated with the user on a registration:
```
https://example.com?utm_source=twitter&utm_medium=desktop
https://example.com?utm_source=twitter&utm_medium=mobile
https://example.com?ref=google&adGroup=banner
```

## Invalid Tracking URLs

And these will be not (cause they are not defined in the config):
```
https://example.com?my_source=twitter&my_medium=desktop
https://example.com?my_ref=google&group=banner
```

## HTTP Referer Fallback

In instances where a user navigates to your application without any tracking parameters attached to the URL, the **HTTP referer**, when available, is utilized as the **default** source.

This scenario often arises when individuals link to your app from their blogs or websites without embedding UTM codes in the URL. Ordinarily, the absence of UTM parameters would obscure the origin of these visitors. However, by leveraging the HTTP referer, you can still ascertain the referring site, ensuring **valuable insights** into your traffic _sources_ are not lost.
