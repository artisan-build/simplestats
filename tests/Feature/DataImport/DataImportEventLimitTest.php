<?php

use App\Enums\DataImportType;
use App\Imports\BaseStatsImport;
use App\Models\DataImport;
use App\Models\Project;
use App\Models\StatsUser;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->team->update(['timezone' => 'UTC']);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

function makeImporter($team, $project): BaseStatsImport
{
    $dataImport = new DataImport;
    $dataImport->rows_max = 10;
    $dataImport->team_id = $team->id;
    $dataImport->project_id = $project->id;
    $dataImport->setRelation('team', $team);

    return DataImportType::USERS->getImporter($dataImport);
}

test('hasReachedMonthlyEventLimit accounts for a delta of pending events', function () {
    $limit = $this->team->owner->sparkPlan()->options['Events'];
    $this->team->update(['events_count_monthly' => $limit - 5]);
    $this->team->refresh();

    $service = app(SubscriptionService::class);

    expect($service->hasReachedMonthlyEventLimit($this->team))->toBeFalse()
        ->and($service->hasReachedMonthlyEventLimit($this->team, 4))->toBeFalse()
        ->and($service->hasReachedMonthlyEventLimit($this->team, 5))->toBeTrue();
});

test('import throws when the monthly event limit is reached', function () {
    $limit = $this->team->owner->sparkPlan()->options['Events'];
    $this->team->update(['events_count_monthly' => $limit]);
    $this->team->refresh();

    $importer = makeImporter($this->team, $this->project);

    expect(fn () => $importer->model(['id' => 1, 'time' => now()->format('Y-m-d H:i:s')]))
        ->toThrow(AuthorizationException::class, 'Monthly event limit reached.');
});

test('import builds the model when the team is under the event limit', function () {
    $importer = makeImporter($this->team, $this->project);

    $model = $importer->model(['id' => 1, 'time' => now()->format('Y-m-d H:i:s')]);

    expect($model)->toBeInstanceOf(StatsUser::class)
        ->and($importer->counter)->toBe(1);
});
