<?php

use App\Models\Team;
use App\Models\User;
use App\Services\Ai\TeamAiConfig;
use Database\Seeders\TestUserSeeder;
use Laravel\Ai\AiManager;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->team = Team::find($this->user->current_team_id);
});

it('activates nothing without team settings', function (): void {
    expect(resolve(TeamAiConfig::class)->activate($this->team))->toBeNull()
        ->and(resolve(TeamAiConfig::class)->activate(null))->toBeNull();
});

it('registers the synthetic provider with the team key and model', function (): void {
    $this->team->forceFill([
        'ai_provider' => 'anthropic',
        'ai_model' => 'claude-sonnet-5',
        'ai_api_key' => 'team-secret-key',
    ])->save();

    $provider = resolve(TeamAiConfig::class)->activate($this->team->fresh());

    expect($provider)->toBe(TeamAiConfig::PROVIDER_NAME)
        ->and(config('ai.providers.team-byok.driver'))->toBe('anthropic')
        ->and(config('ai.providers.team-byok.key'))->toBe('team-secret-key')
        ->and(config('ai.providers.team-byok.models.text.default'))->toBe('claude-sonnet-5');
});

it('purges a previously cached synthetic provider instance', function (): void {
    $this->team->forceFill(['ai_provider' => 'gemini', 'ai_api_key' => 'first-key'])->save();
    resolve(TeamAiConfig::class)->activate($this->team->fresh());
    resolve(AiManager::class)->instance(TeamAiConfig::PROVIDER_NAME);

    $otherTeam = Team::factory()->create([
        'user_id' => $this->user->id,
        'ai_provider' => 'gemini',
        'ai_api_key' => 'second-key',
    ]);
    resolve(TeamAiConfig::class)->activate($otherTeam);

    expect(resolve(AiManager::class)->instance(TeamAiConfig::PROVIDER_NAME)->providerCredentials()['key'])->toBe('second-key');
});

it('rejects providers outside the allowed list', function (): void {
    $this->team->forceFill(['ai_provider' => 'eleven', 'ai_api_key' => 'key'])->save();

    expect(resolve(TeamAiConfig::class)->activate($this->team->fresh()))->toBeNull();
});

it('stores team ai settings encrypted and never returns the key', function (): void {
    $response = $this->actingAs($this->user)->put(route('teams.ai-settings.update', $this->team), [
        'ai_provider' => 'anthropic',
        'ai_model' => null,
        'ai_api_key' => 'super-secret',
    ]);

    $response->assertRedirect();

    $this->team->refresh();

    expect($this->team->ai_provider)->toBe('anthropic')
        ->and($this->team->ai_api_key)->toBe('super-secret')
        ->and($this->team->getRawOriginal('ai_api_key'))->not->toContain('super-secret')
        ->and($this->team->toArray())->not->toHaveKey('ai_api_key');

    $show = $this->actingAs($this->user)->get(route('teams.show', $this->team));
    $show->assertSuccessful();
    expect($show->getContent())->not->toContain('super-secret');
});

it('keeps the stored key when only the model changes', function (): void {
    $this->team->forceFill(['ai_provider' => 'anthropic', 'ai_api_key' => 'keep-me'])->save();

    $this->actingAs($this->user)->put(route('teams.ai-settings.update', $this->team), [
        'ai_provider' => 'anthropic',
        'ai_model' => 'claude-opus-4-8',
        'ai_api_key' => null,
    ])->assertRedirect();

    expect($this->team->fresh())
        ->ai_api_key->toBe('keep-me')
        ->ai_model->toBe('claude-opus-4-8');
});

it('requires a key when enabling a provider without a stored key', function (): void {
    $this->actingAs($this->user)->putJson(route('teams.ai-settings.update', $this->team), [
        'ai_provider' => 'anthropic',
    ])->assertUnprocessable()->assertJsonValidationErrors('ai_api_key');
});

it('clears all settings when the provider is unset', function (): void {
    $this->team->forceFill(['ai_provider' => 'gemini', 'ai_model' => 'x', 'ai_api_key' => 'y'])->save();

    $this->actingAs($this->user)->put(route('teams.ai-settings.update', $this->team), [
        'ai_provider' => null,
    ])->assertRedirect();

    expect($this->team->fresh())
        ->ai_provider->toBeNull()
        ->ai_model->toBeNull()
        ->ai_api_key->toBeNull();
});

it('forbids non-admin team members from updating ai settings', function (): void {
    $member = User::factory()->create();
    $this->team->users()->attach($member, ['role' => 'viewer']);

    auth()->forgetGuards();

    $this->actingAs($member)->putJson(route('teams.ai-settings.update', $this->team), [
        'ai_provider' => 'anthropic',
        'ai_api_key' => 'nope',
    ])->assertForbidden();
});
