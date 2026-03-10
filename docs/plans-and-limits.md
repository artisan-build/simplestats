# Plans and Limits

This page provides an overview of all plan features and technical limits.

## Plan Comparison

<div class="full-width-table">

| Feature              | Free       | Starter    | Plus       | Business   | Enterprise   |
|----------------------|------------|------------|------------|------------|--------------|
| Projects             | 1          | 10         | Unlimited  | Unlimited  | Unlimited    |
| Monthly Events       | 5,000      | 100,000    | 200,000    | 500,000    | 10,000,000   |
| API Rate Limit       | 200/min    | 500/min    | 1,000/min  | 2,500/min  | 5,000/min    |
| Multi-Currency       | -          | Yes        | Yes        | Yes        | Yes          |
| Data Import          | -          | Yes        | Yes        | Yes        | Yes          |

</div>

<style>
.full-width-table table {
  width: 100%;
  display: table;
}
.full-width-table td,
.full-width-table th {
  text-align: center;
}
.full-width-table td:first-child,
.full-width-table th:first-child {
  text-align: left;
}
</style>

## What is an Event?

An event is any tracked action sent to the SimpleStats API. This includes:

- **Visitor tracking** - Each unique visitor counts as one event
- **User registration** - Each new user registration counts as one event
- **Login tracking** - Each tracked login counts as one event
- **Payment tracking** - Each tracked payment counts as one event

## API Rate Limits

Rate limits protect our infrastructure and ensure fair usage across all customers. If you exceed your rate limit, the API will return a `429 Too Many Requests` response.

The rate limit is applied per project (API token) per minute. For example, on the Plus plan you can send up to 2,000 API requests per minute per project.

If you need higher limits, please [contact us](mailto:simplestats@laracraft.tech).
