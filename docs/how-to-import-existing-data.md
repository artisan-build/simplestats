# How to import existing data

Integrating _SimpleStats_ into your existing application doesn't mean leaving behind your historical data. Recognizing the value of your application's past insights, we've developed the **Data Import** feature. This functionality enables a smooth transition by allowing you to seamlessly **import** your **existing data** into _SimpleStats_, ensuring that you retain access to all historical metrics and insights. 

::: info
The Data Import feature is not available on the free subscription plan.
:::

## Supported Formats

The Data Import supports the following file formats: _**xls, xlsx**_ and _**csv**_.

## Import Capabilities

The import feature is designed to manage **substantial** volumes of data efficiently. Therefore, unless you approach the limits of your subscription, we encourage you to import the **entirety** of your data. This capability ensures that you can leverage the full extent of your **historical insights** without reservations.

## Importing User Data

Though it is not mandatory, it's highly recommended to **first** import the **user data** and only then the corresponding payment data. To start a new import, you have to provide a [supported file format](#supported-formats) containing the user data to import.

### Allowed User Fields

The following fields are allowed:

[//]: <> (WARNING !!!!: THIS TABLE SHOULD BE IDENTICALL TO THE ONE ON THE "API DOCS" PAGE.)

| Key                  | Type                 | Description                                                                                                                 |
|----------------------|----------------------|-----------------------------------------------------------------------------------------------------------------------------|
| **id**               | _integer_ `required` | The user id.<br/>(`uuid` and `ulid` are not supported at the moment)                                                        |
| **time**             | _string_ `required`  | The time when the user has registerd.<br/>Format: `Y-m-d`, before or equal `today`                                          |
| **ip**               | _string_ `nullable`  | The `ip` of the user. _(does not get stored)_                                                                               |
| **location_country** | _string_ `nullable`  | The tracking `country` of the user. (If the `ip` is set, we take the country that refers to it.)                            |
| **location_region**  | _string_ `nullable`  | The tracking `region` of the user. (If the `ip` is set, we take the region that refers to it.)                              |
| **location_city**    | _string_ `nullable`  | The tracking `city` of the user. (If the `ip` is set, we take the city that refers to it.)                                  |
| **user_agent**       | _string_ `nullable`  | The `user_agent` of the user. _(does not get stored)_                                                                       |
| **device_type**      | _string_ `nullable`  | The tracking `device_type` of the user. (If the `user_agent` is set, we take the device type that refers to it.)            |
| **device_platform**  | _string_ `nullable`  | The tracking `device_platform` (OS) of the user. (If the `user_agent` is set, we take the device system that refers to it.) |
| **device_browser**   | _string_ `nullable`  | The tracking `device_browser` of the user. (If the `user_agent` is set, we take the device browser that refers to it.)      |
| **track_referer**    | _string_ `nullable`  | The tracking `referer` of the user.                                                                                         |
| **track_source**     | _string_ `nullable`  | The tracking `source` of the user.                                                                                          |
| **track_medium**     | _string_ `nullable`  | The tracking `medium` of the user.                                                                                          |
| **track_campaign**   | _string_ `nullable`  | The tracking `campaign` of the user.                                                                                        |
| **track_term**       | _string_ `nullable`  | The tracking `term` of the user.                                                                                            |
| **track_content**    | _string_ `nullable`  | The tracking `content` of the user.                                                                                         |

Here is an **example** of how such an SQL query may look like:

```sql
SELECT
    id,
    DATE_FORMAT(created_at, '%Y-%m-%d') as `time`
FROM
    users
WHERE
    1;
```

### With Condition

Sometimes you may not want to count all users as "valid" registrations. So you may only export the users which pass a certain condition, such as the user has verified their email address.

The query could look like this:

```sql
SELECT
    id,
    DATE_FORMAT(created_at, '%Y-%m-%d') AS `time`
FROM
    users
WHERE
    email_verified_at IS NOT NULL;
```

Now export the output of the query, save the file and then you can **upload** the file inside the app to start a new import!

::: info
Please note, the provided queries serve merely as **examples**. Your actual table structures and data might vary significantly. The implementation is completely up to you!
:::

::: warning
The first row of your import file must be a **heading row** (_a row in which each cell contains the key of the [allowed fields](#allowed-user-fields)_)!
:::

### Simulated Natural Login Behavior (SNLB)

_Coming soon_: In many cases, historical login data for users isn't readily available. To address this, we're introducing an upcoming feature that allows for the optional inclusion of a `last_known_activity` field. This field could correspond to the `updated_at` attribute, a `last_login` field, or any similar date in your `users` table that best approximates the user's most recent activity.

By supplying this information, we will generate simulated login entries to mimic natural login patterns, based on the assumption that each user logged in at least **once a week** from their registration date to their _last known activity_. This enhancement aims to provide a richer, more accurate representation of your historical user engagement over time.

## Importing Payment Data

After you've imported your user data, you may start another import for your payment data. Again you have to provide a [supported file format](#supported-formats) containing the payment data to import.

### Allowed Payment Fields

The following fields are allowed:

[//]: <> (WARNING !!!!: THIS TABLE SHOULD BE IDENTICALL TO THE ONE ON THE "API DOCS" PAGE.)

| Key               | Type                 | Description                                                                      |
|-------------------|----------------------|----------------------------------------------------------------------------------|
| **id**            | _integer_ `required` | The payment id.<br/>(`uuid` and `ulid` are not supported at the moment)          |
| **stats_user_id** | _integer_ `required` | The user id associated with the payment.                                         |
| **time**          | _string_ `required`  | The time when the payment happened.<br/>Format: `Y-m-d`, before or equal `today` |
| **gross**         | _numeric_ `required` | The gross amount of the payment in cents.<br/>(e.g.: $1 = 100 Cent)              |
| **net**           | _numeric_ `required` | The net amount of the payment in cents.<br/>(e.g.: $1 = 100 Cent).               |
| **currency**      | _string_ `required`  | The **ISO-4217** currency code of the payment.<br/>(e.g.: EUR, USD, GBP ...)     |

Assuming the table is called `transactions`, here is an **example** of how such an SQL query may look like:

```sql
SELECT
    id,
    user_id AS stats_user_id,
    total AS gross,
    total - tax AS net,
    'USD' AS currency,
    DATE_FORMAT(created_at, '%Y-%m-%d') as `time`
FROM
    transactions
WHERE
    1;
```

### With Condition

Sometimes you may not want to count all payments as "valid". So you may only export the payments which pass a certain condition, such as having a "completed" status.

The query could look like this:

```sql
SELECT
    id,
    user_id AS stats_user_id,
    total AS gross,
    total - tax AS net,
    'USD' AS currency,
    DATE_FORMAT(created_at, '%Y-%m-%d') as `time`
FROM
    transactions
WHERE
    status = 'completed';
```

Now export the output of the query, save the file and then you can **upload** the file inside the app to start a new import!

::: info
Please note, the provided queries serve merely as **examples**. Your actual table structures and data might vary significantly. The implementation is completely up to you!
:::

::: warning
The first row of your import file must be a **heading row** (_a row in which each cell contains the key of the [allowed fields](#allowed-payment-fields)_)!
:::

::: danger
Remember that **Gross** and **Net** must be stated in cents!
:::
