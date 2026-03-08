<?php

use App\Models\CustomStatsRangePreset;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
});

test('user can create a custom preset', function () {
    $response = $this->postJson(route('custom-presets.store'), [
        'name' => 'Last 14 Days',
        'range_amount' => 14,
        'range_type' => 'days',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['id', 'name']);
    expect($response->json('name'))->toBe('Last 14 Days');
    expect($this->user->customStatsRangePresets)->toHaveCount(1);
});

test('user cannot exceed 10 custom presets', function () {
    CustomStatsRangePreset::factory()->count(10)->create(['user_id' => $this->user->id]);

    $response = $this->postJson(route('custom-presets.store'), [
        'name' => 'One Too Many',
        'range_amount' => 7,
        'range_type' => 'days',
    ]);

    $response->assertStatus(422);
});

test('user can update their own preset', function () {
    $preset = CustomStatsRangePreset::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson(route('custom-presets.update', $preset), [
        'name' => 'Updated Name',
        'range_amount' => 30,
        'range_type' => 'months',
    ]);

    $response->assertOk();
    expect($preset->fresh()->name)->toBe('Updated Name');
    expect($preset->fresh()->range_amount)->toBe(30);
});

test('user cannot update another users preset', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $preset = CustomStatsRangePreset::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->putJson(route('custom-presets.update', $preset), [
        'name' => 'Hijacked',
        'range_amount' => 7,
        'range_type' => 'days',
    ]);

    $response->assertForbidden();
});

test('user can delete their own preset', function () {
    $preset = CustomStatsRangePreset::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson(route('custom-presets.destroy', $preset));

    $response->assertNoContent();
    expect(CustomStatsRangePreset::find($preset->id))->toBeNull();
});

test('user cannot delete another users preset', function () {
    $otherUser = User::factory()->withPersonalTeam()->create();
    $preset = CustomStatsRangePreset::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->deleteJson(route('custom-presets.destroy', $preset));

    $response->assertForbidden();
});

test('validation requires name', function () {
    $response = $this->postJson(route('custom-presets.store'), [
        'range_amount' => 7,
        'range_type' => 'days',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('name');
});

test('validation requires valid range_type', function () {
    $response = $this->postJson(route('custom-presets.store'), [
        'name' => 'Test',
        'range_amount' => 7,
        'range_type' => 'hours',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('range_type');
});

test('validation requires range_amount between 1 and 60', function () {
    $response = $this->postJson(route('custom-presets.store'), [
        'name' => 'Test',
        'range_amount' => 0,
        'range_type' => 'days',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('range_amount');

    $response = $this->postJson(route('custom-presets.store'), [
        'name' => 'Test',
        'range_amount' => 61,
        'range_type' => 'days',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('range_amount');
});

test('user can toggle builtin preset visibility', function () {
    $response = $this->postJson(route('custom-presets.toggle-builtin'), [
        'preset' => 'yesterday',
    ]);

    $response->assertOk();
    expect($this->user->fresh()->settings['hiddenPresets'])->toContain('yesterday');

    $response = $this->postJson(route('custom-presets.toggle-builtin'), [
        'preset' => 'yesterday',
    ]);

    $response->assertOk();
    expect($this->user->fresh()->settings['hiddenPresets'])->not->toContain('yesterday');
});

test('toggle builtin rejects invalid preset', function () {
    $response = $this->postJson(route('custom-presets.toggle-builtin'), [
        'preset' => 'nonexistent',
    ]);

    $response->assertUnprocessable();
});

test('user can list their custom presets', function () {
    CustomStatsRangePreset::factory()->count(3)->create(['user_id' => $this->user->id]);

    $response = $this->getJson(route('custom-presets.index'));

    $response->assertOk();
    $response->assertJsonCount(3);
});

test('custom preset toOption returns correct format', function () {
    $preset = CustomStatsRangePreset::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Last 14 Days',
    ]);

    $option = $preset->toOption();

    expect($option['id'])->toBe('custom_'.$preset->id);
    expect($option['name'])->toBe('Last 14 Days');
});

test('custom preset toRange returns correct format', function () {
    $preset = CustomStatsRangePreset::factory()->create([
        'user_id' => $this->user->id,
        'range_amount' => 14,
        'range_type' => 'days',
    ]);

    $range = $preset->toRange();

    expect($range['rangeAmount'])->toBe(14);
    expect($range['rangeType'])->toBe('days');
    expect($range['rangeStart'])->not->toBeNull();
});
