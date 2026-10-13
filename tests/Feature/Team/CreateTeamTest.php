<?php

use App\Models\User;

test('default team will be created', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    expect($user->fresh()->ownedTeams)->toHaveCount(1);
});
