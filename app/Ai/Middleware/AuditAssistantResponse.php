<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Prompts\AgentPrompt;

class AuditAssistantResponse
{
    /**
     * Audit-only guardrail: flags suspicious assistant output (prompt
     * injection echoes, links to foreign hosts) in the log without blocking
     * anything. The response has already streamed to the browser when the
     * then() callback runs, so blocking here would be theater anyway; the
     * hard mitigation is the same-origin link allowlist in the frontend
     * renderer (simpleMarkdown.js).
     */
    public function handle(AgentPrompt $prompt, Closure $next): mixed
    {
        return $next($prompt)->then(function ($response) use ($prompt) {
            $text = (string) ($response->text ?? $response);
            $findings = $this->findings($text);

            if ($findings !== []) {
                Log::warning('AI assistant response flagged: '.implode(', ', $findings), [
                    'user_id' => auth()->id(),
                    'prompt' => Str::limit($prompt->prompt, 200),
                    'response' => Str::limit($text, 500),
                ]);
            }
        });
    }

    /**
     * @return list<string>
     */
    private function findings(string $text): array
    {
        $findings = [];

        if (preg_match('/ignore\s+(all\s+)?(previous|prior|your)\s+instructions/i', $text)) {
            $findings[] = 'prompt injection echo';
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if (preg_match_all('/https?:\/\/([^\/\s"()\[\]]+)/i', $text, $matches)) {
            $foreignHosts = collect($matches[1])
                ->map(fn (string $host) => Str::lower($host))
                ->unique()
                ->reject(fn (string $host) => $host === $appHost)
                ->values();

            if ($foreignHosts->isNotEmpty()) {
                $findings[] = 'external url ('.$foreignHosts->implode(', ').')';
            }
        }

        return $findings;
    }
}
