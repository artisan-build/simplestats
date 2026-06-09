<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\User;
use App\Services\StatsCustomEventService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->team->update(['timezone' => 'UTC']);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);

    // Last 7 days, ending now.
    $this->filters = fn (int $projectId = StatsFilterDto::TRACK_ALL) => new StatsFilterDto(
        projectId: $projectId,
        rangeAmount: 7,
        rangeType: StatsRangeType::DAY,
        rangeStart: CarbonImmutable::parse(now()),
        comparison: '0',
    );

    $this->createEvent = function (Project $project, CarbonImmutable $time) {
        $name = StatsCustomEventName::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
        ]);

        return StatsCustomEvent::factory()->create([
            'team_id' => $this->team->id,
            'project_id' => $project->id,
            'stats_custom_event_name_id' => $name->id,
            'stats_user_id' => null,
            'stats_visitor_id' => null,
            'time' => $time,
            'time_utc' => $time,
        ]);
    };
});

test('hasCustomEvents is false without any events', function () {
    expect(StatsCustomEventService::hasCustomEvents(($this->filters)()))->toBeFalse();
});

test('hasCustomEvents is true with an event in range', function () {
    ($this->createEvent)($this->project, now());

    expect(StatsCustomEventService::hasCustomEvents(($this->filters)()))->toBeTrue();
    expect(StatsCustomEventService::hasCustomEvents(($this->filters)($this->project->id)))->toBeTrue();
});

test('hasCustomEvents stays true for events outside the selected range', function () {
    // Period-independent: an event tracked long ago still shows the group, so
    // it does not vanish when the range shifts.
    ($this->createEvent)($this->project, now()->subDays(30));

    expect(StatsCustomEventService::hasCustomEvents(($this->filters)()))->toBeTrue();
});

test('hasCustomEvents scopes to the selected project', function () {
    $otherProject = Project::factory()->create(['team_id' => $this->team->id]);
    ($this->createEvent)($otherProject, now());

    // Visible in the merged "all projects" view...
    expect(StatsCustomEventService::hasCustomEvents(($this->filters)()))->toBeTrue();
    // ...but not when a different project is selected.
    expect(StatsCustomEventService::hasCustomEvents(($this->filters)($this->project->id)))->toBeFalse();
});
