# MCP Server

_SimpleStats_ ships a hosted [MCP](https://modelcontextprotocol.io) (Model Context Protocol) server. Connect it to AI tools like Claude Code, Cursor, or Claude Desktop and ask questions about your analytics in plain language: "How many registrations did I get last month?", "Which referrer brings the most revenue?", "How did my MRR develop this year?".

::: tip
Prefer chatting inside the app? The same assistant powers the **Ask AI** panel on your dashboard.
:::

## Available Tools

| Tool | Purpose |
|------|---------|
| `list-stats-capabilities` | Lists your projects, breakdown dimensions, date presets, and a KPI legend |
| `get-kpi-stats` | KPI time series (visitors, registrations, conversion, revenue, active users) with totals |
| `get-grouped-breakdown` | Breakdowns by dimension (referrer, source, campaign, country, device, ...) |
| `get-recurring-revenue-stats` | MRR/ARR snapshots, movements, churn, NRR/GRR, subscription economics |
| `get-retention-cohorts` | Registration retention cohorts |
| `get-campaign-performance` | Per-campaign profitability: spend, revenue, net profit, ROAS, CAC, LTV:CAC, payback |
| `search-docs` | Searches this documentation |
| `list-app-pages` | Lists the in-app pages with descriptions and links |

## Endpoint & Authentication

The MCP server is available via streamable HTTP at:

`https://simplestats.io/mcp`

Authenticate with your **project API token** as a bearer token, the same token you use for the [Ingest API](ingest-api.md) and [Query API](query-api.md):

`Authorization: Bearer API_TOKEN_HERE`

::: tip
You'll receive an API token after creating a new project, or you can find your existing API tokens inside the app on the project page.
:::

All data is scoped to the team the project belongs to. When you don't specify a project in your question, results default to the token's project.

## Claude Code

Add the server with one command:

```bash
claude mcp add --transport http simplestats https://simplestats.io/mcp \
  --header "Authorization: Bearer API_TOKEN_HERE"
```

## Cursor / VS Code

Add the server to your `mcp.json`:

```json
{
    "mcpServers": {
        "simplestats": {
            "url": "https://simplestats.io/mcp",
            "headers": {
                "Authorization": "Bearer API_TOKEN_HERE"
            }
        }
    }
}
```

## Claude Desktop

Claude Desktop connectors currently expect OAuth for remote servers. Use the [mcp-remote](https://www.npmjs.com/package/mcp-remote) shim to bridge with your bearer token instead. Add this to your `claude_desktop_config.json`:

```json
{
    "mcpServers": {
        "simplestats": {
            "command": "npx",
            "args": [
                "mcp-remote",
                "https://simplestats.io/mcp",
                "--header",
                "Authorization: Bearer API_TOKEN_HERE"
            ]
        }
    }
}
```

## Rate Limit

MCP requests are limited to 60 requests per minute per project. If you exceed the limit, the server responds with `429 Too Many Requests`.

## Self-Hosted

On self-hosted installations the MCP server is available on your own domain at `https://your-domain.example/mcp`, authenticated with your project API tokens the same way.
