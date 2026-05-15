# Bot Detection

_SimpleStats_ filters out **bot and crawler traffic** automatically on the client side, so your visitor counts reflect real humans, not scrapers or uptime monitors. Bot detection is enabled by default and requires no configuration.

## How it works

Inside the `CheckTracking` middleware of the [laravel-client package](https://github.com/simplestats-io/laravel-client/blob/main/src/Middleware/CheckTracking.php), every incoming request is checked before any tracking happens. A request is **skipped** (no visitor recorded, no API call dispatched) when:

- the request has **no User-Agent string** at all (most aggressive scrapers send none), or
- the User-Agent matches a **known bot signature** via [matomo/device-detector](https://github.com/matomo-org/device-detector), covering thousands of known bots, crawlers, scrapers, AI/LLM crawlers (GPTBot, ClaudeBot, PerplexityBot, etc.), social media previewers, and uptime monitors
