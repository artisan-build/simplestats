# API Documentation

Even though the composer client package is dedicated to Laravel, the API basically works with **every client**! So if you'd like to use the tool, even if you're not using Laravel, you may just trigger the API requests manually.

## Authentication

In order to use the API, you should authenticate your request by including your API token as a bearer token value:

`Authorization: Bearer API_TOKEN_HERE`

You'll receive an API token after creating a new instance, our you can find your existing API tokens inside the app on the instance page.

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

## Endpoints

At the moment, only the create-endpoints are available. 

### Create User

**HTTP Request**

`POST /api/v1/stats-user`

**Parameters**

| Key      | Type                 | Description                                                                        |
|----------|----------------------|------------------------------------------------------------------------------------|
| **id**   | _integer_ `required` | The user id.<br/>(`uuid` and `ulid` are not supported at the moment)               |
| **time** | _string_ `required`  | The time when the user has registerd.<br/>Format: `Y-m-d`, before or equal `today` |
| **track_source** | _string_ `nullable`  | The tracking `source` of the user.                                                 |
| **track_medium** | _string_ `nullable`  | The tracking `medium` of the user.                                                 |
| **track_campaign** | _string_ `nullable`  | The tracking `campaign` of the user.                                               |
| **track_term** | _string_ `nullable`  | The tracking `term` of the user.                                                   |
| **track_content** | _string_ `nullable`  | The tracking `content` of the user.                                                |

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
    "data": {
        "id": "1",
        "project_id": 1,
        "instance_id": 1,
        "stats_track_source_id": 1,
        "stats_track_medium_id": null,
        "stats_track_campaign_id": null,
        "stats_track_term_id": null,
        "stats_track_content_id": null,
        "time": "2024-04-04"
    }
}
```

### Create Login

**HTTP Request**

`POST /api/v1/stats-login`

**Parameters**

| Key               | Type                 | Description                                                                         |
|-------------------|----------------------|-------------------------------------------------------------------------------------|
| **stats_user_id** | _integer_ `required` | The id of the user trying to login.                                                 |
| **time**          | _string_ `required`  | The time when the user tries to login.<br/>Format: `Y-m-d`, before or equal `today` |

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
    "data": {
        "id": 1,
        "stats_user_id": "1",
        "project_id": 1,
        "instance_id": 1,
        "time": "2024-04-04"
    }
}
```

### Create Payment

**HTTP Request**

`POST /api/v1/stats-payment`

**Parameters**

| Key               | Type                 | Description                                                                      |
|-------------------|----------------------|----------------------------------------------------------------------------------|
| **id**            | _integer_ `required` | The payment id.<br/>(`uuid` and `ulid` are not supported at the moment)          |
| **stats_user_id** | _integer_ `required` | The user id associated with the payment.                                         |
| **time**          | _string_ `required`  | The time when the payment happened.<br/>Format: `Y-m-d`, before or equal `today` |
| **gross**         | _numeric_ `required` | The gross amount of the payment in cents.<br/>(e.g.: $1 = 100 Cent)              |
| **net**           | _numeric_ `required` | The net amount of the payment in cents.<br/>(e.g.: $1 = 100 Cent).               |
| **currency**      | _string_ `required`  | The **ISO-4217** currency code of the payment.<br/>(e.g.: EUR, USD, GBP ...)     |

**Payload**

```json
{
    "id": "1",
    "stats_user_id": "1",
    "project_id": 1,
    "instance_id": 1,
    "gross": 2000,
    "net": 1800,
    "currency": "EUR",
    "time": "2024-04-04"
}
```

**Response**

```json
{
    "data": {
        "id": "1",
        "stats_user_id": "1",
        "project_id": 1,
        "instance_id": 1,
        "gross": 2000,
        "net": 1800,
        "currency": "EUR",
        "time": "2024-04-04"
    }
}
```

## Contribution

If you're enthusiastic about contributing and possess the expertise to craft an adapter for other languages and frameworks — be it plain **PHP**, **Django**, or something different — we'd love to hear from you. Your support in extending _SimpleStats_' reach and functionality is invaluable. Feel free to **reach out** to us if you're interested in making a significant impact. Together, we can make _SimpleStats_ even more versatile and accessible to developers across different platforms.
