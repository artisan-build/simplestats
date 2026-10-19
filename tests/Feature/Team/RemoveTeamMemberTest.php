<?php

use App\Actions\CreateUserNotification;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\UserNotification;

test('team members can be removed from teams', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    $user->currentTeam->users()->attach(
        $otherUser = User::factory()->create(), ['role' => 'admin']
    );

    $response = $this->delete('/teams/'.$user->currentTeam->uuid.'/members/'.$otherUser->uuid);

    expect($user->currentTeam->fresh()->users)->toHaveCount(0);
});

// test('only team owner can remove team members', function () {
//    $user = User::factory()->withPersonalTeam()->create();
//
//    $user->currentTeam->users()->attach(
//        $otherUser = User::factory()->create(), ['role' => 'admin']
//    );
//
//    $this->actingAs($otherUser);
//
//    $response = $this->delete('/teams/'.$user->currentTeam->uuid.'/members/'.$user->uuid);
//
//    $response->assertStatus(403);
// });

test('removed team members stop receiving notifications for the team projects only', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team = $owner->currentTeam;

    $teamProject = Project::factory()->create(['team_id' => $team->id]);
    $ownProject = Project::factory()->create(['team_id' => $member->currentTeam->id]);
    resolve(CreateUserNotification::class)($member, $teamProject);

    $team->users()->attach($member, ['role' => 'admin']);

    // The team scope follows the remover's current team, which need not be the managed one.
    $otherTeam = Team::factory()->create(['user_id' => $owner->id]);
    $owner->forceFill(['current_team_id' => $otherTeam->id])->save();

    $this->actingAs($owner)->delete('/teams/'.$team->uuid.'/members/'.$member->uuid);

    expect(UserNotification::whereBelongsTo($member)->whereMorphedTo('notifiable', $teamProject)->exists())->toBeFalse()
        ->and(UserNotification::whereBelongsTo($member)->whereMorphedTo('notifiable', $ownProject)->exists())->toBeTrue()
        ->and(UserNotification::whereBelongsTo($owner)->whereMorphedTo('notifiable', $teamProject)->exists())->toBeTrue();
});
