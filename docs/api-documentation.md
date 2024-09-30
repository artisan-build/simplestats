# API Documentation

Even though the composer client package is dedicated to Laravel, the API basically works with **every client**! So if you'd like to use the tool, even if you're not using Laravel, you may just trigger the API requests manually.

## Authentication

In order to use the API, you should authenticate your request by including your API token as a bearer token value:

`Authorization: Bearer API_TOKEN_HERE`

::: tip
You'll receive an API token after creating a new instance, our you can find your existing API tokens inside the app on the instance page.
:::

## Headers

Make sure you have the following content type headers are set on every request:

`Accept: application/json Content-Type: application/json`

## URI

_SimpleStats_ API is hosted on the following base URI:

`https://simplestats.io/api/v1/`

## Errors

_SimpleStats_ uses standard HTTP response codes to communicate the outcome of **API requests**. These codes offer immediate insight into whether a request has been successful, encountered an error, or requires additional action.

| Code   | Description                                                            |
|--------|------------------------------------------------------------------------|
| **200** | Everything is ok.                                                      |
| **400** | Valid data was given but the request has failed.                       |
| **401** | No valid API Key was given.                                            |
| **404** | The request resource could not be found.                               |
| **422** | The payload has missing required parameters or invalid data was given. |
| **429** | Too many attempts.                                                     |
| **500** | Request failed due to an internal error in _SimpleStats_.              |
| **503** | _SimpleStats_ is offline for maintenance.                              |

## Rate Limit

The rate limits for our API are **120** requests **per minute** by default. If you have special needs for more requests, please contact us to request more capacity.

## Endpoints

At the moment, only the `create` endpoints are available.
But if the `id` already exists on our side, the record gets updated with the new data.

### Create Visitor

**HTTP Request**

`POST /api/v1/stats-visitor`

_Core parameters:_

[//]: <> (WARNING !!!!: THIS TABLE SHOULD BE IDENTICALL TO THE ONE ON THE "IMPORT USER" PAGE.)

| Key                | Type                 | Description                                                                            |
|--------------------|----------------------|----------------------------------------------------------------------------------------|
| **time**           | _string_ `required`  | The time when the user has registerd.<br/>Format: `Y-m-d H:i:s P`, before or equal `now` |
| **ip**             | _string_ `nullable`  | The `ip` of the user. _(does not get stored)_                                          |
| **user_agent**     | _string_ `nullable`  | The `user_agent` of the user. _(does not get stored)_                                  |
| **track_referer**  | _string_ `nullable`  | The http tracking `referer` of the user.                                               |
| **track_source**   | _string_ `nullable`  | The tracking `source` of the user.                                                     |
| **track_medium**   | _string_ `nullable`  | The tracking `medium` of the user.                                                     |
| **track_campaign** | _string_ `nullable`  | The tracking `campaign` of the user.                                                   |
| **track_term**     | _string_ `nullable`  | The tracking `term` of the user.                                                       |
| **track_content**  | _string_ `nullable`  | The tracking `content` of the user.                                                    |
| **page_entry**     | _string_ `nullable`  | The tracking `page_entry` of the user.                                                 |

_Additional parameters:_

| Key                  | Type                 | Description                                                                                                          |
|----------------------|----------------------|----------------------------------------------------------------------------------------------------------------------|
| **location_country** | _string_ `nullable`  | The location `country` of the user. (If the `ip` is set, we take the country that refers to it.)                     |
| **location_region**  | _string_ `nullable`  | The location `region` of the user. (If the `ip` is set, we take the region that refers to it.)                       |
| **location_city**    | _string_ `nullable`  | The location `city` of the user. (If the `ip` is set, we take the city that refers to it.)                           |
| **device_type**      | _string_ `nullable`  | The device `type` of the user. (If the `user_agent` is set, we take the device type that refers to it.)              |
| **device_platform**  | _string_ `nullable`  | The device `platform` (OS) of the user. (If the `user_agent` is set, we take the device platform that refers to it.) |
| **device_browser**   | _string_ `nullable`  | The device `browser` of the user. (If the `user_agent` is set, we take the device browser that refers to it.)        |
| **page_entry**       | _string_ `nullable`  | The page `entry` point of the user.                                                                                  |

**Payload**

```json
{
    "ip": "127.0.0.1",
    "user_agent": "Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36",
    "track_source": "test",
    "time": "2024-04-04"
}
```

**Response**

```json
{
    "message": "Request accepted."
}
```

### Create User

**HTTP Request**

`POST /api/v1/stats-user`

_Core parameters:_

[//]: <> (WARNING !!!!: THIS TABLE SHOULD BE IDENTICALL TO THE ONE ON THE "IMPORT USER" PAGE.)

| Key                | Type                | Description                                                                              |
|--------------------|---------------------|------------------------------------------------------------------------------------------|
| **id**             | _mixed_ `required`  | The user id.                                                                             |
| **time**           | _string_ `required` | The time when the user has registerd.<br/>Format: `Y-m-d H:i:s P`, before or equal `now` |
| **ip**             | _string_ `nullable` | The `ip` of the user. _(does not get stored)_                                            |
| **user_agent**     | _string_ `nullable` | The `user_agent` of the user. _(does not get stored)_                                    |
| **track_referer**  | _string_ `nullable` | The http tracking `referer` of the user.                                                 |
| **track_source**   | _string_ `nullable` | The tracking `source` of the user.                                                       |
| **track_medium**   | _string_ `nullable` | The tracking `medium` of the user.                                                       |
| **track_campaign** | _string_ `nullable` | The tracking `campaign` of the user.                                                     |
| **track_term**     | _string_ `nullable` | The tracking `term` of the user.                                                         |
| **track_content**  | _string_ `nullable` | The tracking `content` of the user.                                                      |
| **page_entry**     | _string_ `nullable` | The tracking `page_entry` of the user.                                                   |

_Additional parameters:_

| Key                  | Type                 | Description                                                                                                          |
|----------------------|----------------------|----------------------------------------------------------------------------------------------------------------------|
| **location_country** | _string_ `nullable`  | The location `country` of the user. (If the `ip` is set, we take the country that refers to it.)                     |
| **location_region**  | _string_ `nullable`  | The location `region` of the user. (If the `ip` is set, we take the region that refers to it.)                       |
| **location_city**    | _string_ `nullable`  | The location `city` of the user. (If the `ip` is set, we take the city that refers to it.)                           |
| **device_type**      | _string_ `nullable`  | The device `type` of the user. (If the `user_agent` is set, we take the device type that refers to it.)              |
| **device_platform**  | _string_ `nullable`  | The device `platform` (OS) of the user. (If the `user_agent` is set, we take the device platform that refers to it.) |
| **device_browser**   | _string_ `nullable`  | The device `browser` of the user. (If the `user_agent` is set, we take the device browser that refers to it.)        |
| **page_entry**       | _string_ `nullable`  | The page `entry` point of the user.                                                                                  |

**Payload**

```json
{
    "id": 1,
    "track_source": "test",
    "time": "2024-04-04"
}
```

**Response**

```json
{
    "message": "Request accepted."
}
```

### Create Login

**HTTP Request**

`POST /api/v1/stats-login`

**Parameters**

| Key               | Type                 | Description                                                                         |
|-------------------|----------------------|-------------------------------------------------------------------------------------|
| **stats_user_id** | _integer_ `required` | The id of the user trying to login.                                                 |
| **time**          | _string_ `required`  | The time when the user tries to login.<br/>Format: `Y-m-d H:i:s P`, before or equal `now` |

**Payload**

```json
{
    "stats_user_id": 1,
    "time": "2024-04-04"
}
```

**Response**

```json
{
    "message": "Request accepted."
}
```

### Create Payment

**HTTP Request**

`POST /api/v1/stats-payment`

**Parameters**

[//]: <> (WARNING !!!!: THIS TABLE SHOULD BE IDENTICALL TO THE ONE ON THE "IMPORT PAYMENT" PAGE.)

| Key               | Type                 | Description                                                                            |
|-------------------|----------------------|----------------------------------------------------------------------------------------|
| **id**            | _mixed_ `required`   | The payment id.                                                                        |
| **stats_user_id** | _integer_ `required` | The user id associated with the payment.                                               |
| **time**          | _string_ `required`  | The time when the payment happened.<br/>Format: `Y-m-d H:i:s P`, before or equal `now` |
| **gross**         | _numeric_ `required` | The gross amount of the payment in cents.<br/>(e.g.: $1 = 100 Cent)                    |
| **net**           | _numeric_ `required` | The net amount of the payment in cents.<br/>(e.g.: $1 = 100 Cent).                     |
| **currency**      | _string_ `required`  | The **ISO-4217** currency code of the payment.<br/>(e.g.: EUR, USD, GBP ...)           |

**Payload**

```json
{
    "id": "1",
    "stats_user_id": "1",
    "gross": 2000,
    "net": 1800,
    "currency": "EUR",
    "time": "2024-04-04"
}
```

**Response**

```json
{
    "message": "Request accepted."
}
```
