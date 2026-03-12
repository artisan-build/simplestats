# Query API: Read Your Laravel Analytics Data Programmatically

Dashboards are great for a quick glance at your numbers. But sometimes you need more. Maybe you want to pipe your analytics into a Slack channel every Monday morning. Maybe you're building a custom reporting dashboard for your team. Or maybe you want your CI pipeline to check whether a deploy tanked your conversion rate.

That's why we built the Query API. It gives your Laravel app full programmatic access to the same analytics data you see in the SimpleStats dashboard, through three simple REST endpoints.

## What the Query API offers

The Query API has three endpoints, each serving a different purpose:

1. **Stats** (`GET /api/v1/stats`): Time-series analytics data. Daily visitors, registrations, revenue, conversion rates, and lifetime metrics. This is your go-to for trends over time.

2. **Groups** (`GET /api/v1/stats/groups`): Discover available filter values. Need the ID for "google" as a traffic source? This endpoint lists all known values for any group (sources, countries, browsers, etc.).

3. **Grouped Stats** (`GET /api/v1/stats/grouped`): Analytics data broken down by a specific group. Top traffic sources, top countries, top browsers, all with visitor counts, registrations, conversion rates, and revenue.

## Getting started

Every request needs two things: your project API token and the right headers.

```bash
curl -X GET "https://simplestats.io/api/v1/stats" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

You'll find your API token on the project page inside the app. Rate limits depend on your plan (200/min on Free, up to 5,000/min on Enterprise).

## Practical examples

### Fetching the last 7 days

The simplest call. No parameters needed, since `last_7_days` is the default:

```bash
GET /api/v1/stats
```

The response gives you daily breakdowns in reverse chronological order:

```json
{
    "data": [
        {
            "date": "2026-03-11",
            "pd_visitor": 42,
            "pd_reg": 5,
            "pd_cr": 11.9,
            "pd_net": 15000,
            "pd_gross": 17850,
            "pd_dau": 12,
            "lt_visitor": 1250,
            "lt_reg": 180,
            "lt_net": 450000,
            "lt_arpu": 2500,
            "lt_arppu": 7500,
            "lt_arpv": 360,
            "lt_pu": 60
        }
    ]
}
```

All revenue values are in cents. So `pd_net: 15000` means $150.00 in net revenue for that day.

### Comparing periods

Want to see if this week performed better than last week? Add the `comparison` parameter:

```bash
GET /api/v1/stats?preset=last_7_days&comparison=period
```

The response includes both `data` (current period) and `data_previous` (previous period) with identical structures, so you can compare them field by field.

You can also use `comparison=year` to compare against the same period last year.

### Finding your top traffic sources

This is where Grouped Stats shines. Get your top 5 sources ranked by visitors:

```bash
GET /api/v1/stats/grouped?stats_type=track_source&limit=5
```

```json
{
    "data": [
        {
            "name": "google",
            "type_id": 3,
            "visitors": 120,
            "reg": 15,
            "cr": 12.5,
            "net": 45000,
            "total": 1
        },
        {
            "name": "twitter",
            "type_id": 7,
            "visitors": 85,
            "reg": 10,
            "cr": 11.76,
            "net": 20000,
            "total": 1
        }
    ]
}
```

Sort by revenue instead? Use `stats_sort=net`. Now you can see which sources actually make money, not just which ones send the most clicks.

You can group by any stats type: `track_source`, `track_campaign`, `location_country`, `device_browser`, `page_entry`, `custom_event_name`, and more.

### Filtering by a specific source

Say you want daily stats for only Google traffic. First, find Google's ID using the Groups endpoint:

```bash
GET /api/v1/stats/groups?stats_type=track_source
```

This returns all known sources with their IDs. Find `google` (let's say it's `id: 3`), then use it as a filter:

```bash
GET /api/v1/stats?preset=last_30_days&track_source=3
```

Now you get 30 days of stats filtered to Google traffic only. You can combine multiple filters too: add `location_country=14` to narrow it down to Google traffic from a specific country.

### Custom date ranges

Presets like `last_7_days` and `this_month` cover most cases, but you can also build custom ranges:

```bash
GET /api/v1/stats?range_type=weeks&range_amount=4&range_start=2026-03-01
```

This returns 4 weeks of data, calculated backwards from March 1st. Available granularities: `hours`, `days`, `weeks`, `months`, `years`.

## What you can build with this

**Weekly Slack reports.** A simple cron job that hits the Stats endpoint every Monday morning and posts a summary to your team channel. Visitors, registrations, conversion rate, revenue. No manual reporting.

**Custom dashboards.** Pull data into your Laravel admin panel or a tool like Grafana. Combine analytics with your internal metrics for a complete picture.

**CSV exports.** Fetch grouped stats for all sources over the last quarter, format as CSV, and share with your marketing team. They get the data in a format they already know.

**CI/CD performance checks.** After a deploy, compare the last 24 hours against the previous 24 hours. If conversion rate drops by more than 20%, flag it before it becomes a bigger problem.

**Automated alerts.** Monitor your ARPV (average revenue per visitor) daily from your Laravel app. If it drops below a threshold, send yourself an alert. Catch revenue issues before they compound.

## Full documentation

These examples cover the most common patterns, but the API supports much more: 11 date presets, 15+ filter groups, and flexible custom ranges.

Check out the [full Query API documentation](https://simplestats.io/docs/query-api.html) for the complete reference, including all available presets, stats types, and response fields.
