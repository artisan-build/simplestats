<?php

namespace App\Services\Ai;

use App\Models\Team;
use Laravel\Ai\AiManager;

class TeamAiConfig
{
    /**
     * Synthetic provider name: never collides with the app-level provider
     * entries, which stay cached inside the AiManager singleton with the
     * app's own keys baked in.
     */
    public const PROVIDER_NAME = 'team-byok';

    /**
     * Text-capable laravel/ai providers a team may bring its own key for.
     * Intersected with config('ai.providers') so only providers this app
     * actually configures are offered.
     */
    private const TEXT_PROVIDERS = ['anthropic', 'gemini', 'openai', 'groq', 'deepseek', 'mistral', 'xai', 'openrouter'];

    /**
     * @return list<string>
     */
    public static function allowedProviders(): array
    {
        return array_values(array_intersect(self::TEXT_PROVIDERS, array_keys(config('ai.providers', []))));
    }

    /**
     * Register the team's own provider/key/model under the synthetic provider
     * name and return that name to pass to ->stream(), or null to use the
     * app defaults. Deliberately no failover to the app's key: a broken team
     * key should surface, not silently burn the app's quota.
     */
    public function activate(?Team $team): ?string
    {
        if (! $team?->ai_provider || ! $team->ai_api_key || ! in_array($team->ai_provider, self::allowedProviders(), true)) {
            return null;
        }

        $config = config("ai.providers.{$team->ai_provider}");
        $config['key'] = $team->ai_api_key;

        if ($team->ai_model) {
            data_set($config, 'models.text.default', $team->ai_model);
        }

        config()->set('ai.providers.'.self::PROVIDER_NAME, $config);

        // Provider instances are cached per name inside the AiManager singleton
        // with their config baked in at construction; purge so a long-running
        // queue worker never reuses another team's key.
        app(AiManager::class)->forgetInstance(self::PROVIDER_NAME);

        return self::PROVIDER_NAME;
    }
}
