<?php

use App\Models\User;

test('team names can be updated', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $response = $this->put('/teams/'.$user->currentTeam->uuid, [
        'name' => 'Test Team',
        'currency' => 'EUR',
        'timezone' => 'Europe/Berlin',
    ]);

    expect($user->fresh()->ownedTeams)->toHaveCount(1);
    expect($user->currentTeam->fresh()->name)->toEqual('Test Team');
});
