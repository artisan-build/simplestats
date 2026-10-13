<?php

use App\DTOs\StatsFilterDto;
use App\Enums\StatsRangeType;
use App\Helpers\AppHelper;
use App\Models\Project;
use App\Models\StatsCustomEvent;
use App\Models\StatsCustomEventName;
use App\Models\StatsCustomPropertyName;
use App\Models\StatsCustomPropertyValue;
use App\Models\StatsPayment;
use App\Models\StatsTrackSource;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\User;
use App\Services\StatsAggregationsService;
use App\Services\StatsCustomPropertyService;
use Database\Seeders\TestUserSeeder;

beforeEach(function (): void {
    $this->seed(TestUserSeeder::class);
    $this->user = User::where('email', config('app.test.user')['email'])->firstOrFail();
    $this->project = Project::first();

    $this->actingAs($this->user);
    AppHelper::setTeamContextFromAuth($this->user);
});

function propertyFilters(?Project $project, int $rangeAmount = 30): StatsFilterDto
{
    return new StatsFilterDto(
        projectId: $project?->id ?? 0,
        rangeAmount: $rangeAmount,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
    );
}

test('property aggregation groups registrations, visitors and revenue by value', function (): void {

    // 2 free users, 1 pro user (the pro user has a 100.00 payment)
    $freeUsers = StatsUser::factory(2)->create(['project_id' => $this->project->id, 'time' => now()->subDays(2)]);
    $proUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(2)]);

    foreach ($freeUsers as $freeUser) {
        $freeUser->recordCustomProperties(['ab_test' => 'A']);
    }
    $proUser->recordCustomProperties(['ab_test' => 'B']);

    StatsPayment::factory()->create([
        'project_id' => $this->project->id,
        'stats_user_id' => $proUser->id,
        'net' => 100,
        'currency' => 'EUR',
        'time' => now()->subDay(),
    ]);

    // one visitor on "pro"
    $visitor = StatsVisitor::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(2)]);
    $visitor->recordCustomProperties(['ab_test' => 'B']);

    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');

    $data = resolve(StatsAggregationsService::class)
        ->getAggregatedPropertyData(propertyFilters($this->project), $nameId, 'reg', 10)
        ->keyBy('name');

    expect((int) $data['A']['reg'])->toBe(2)
        ->and((int) $data['B']['reg'])->toBe(1)
        ->and((int) $data['B']['visitors'])->toBe(1)
        ->and((float) $data['B']['net'])->toBe(100.0)
        ->and((float) $data['A']['net'])->toBe(0.0);
});

test('the grouped endpoint returns property data for the dashboard', function (): void {

    $user = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $user->recordCustomProperties(['ab_test' => 'B']);

    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');

    $response = $this->getJson(route('stats.grouped', [
        'project_id' => $this->project->id,
        'stats_type' => 'property',
        'property_name_id' => $nameId,
        'range_type' => 'days',
        'range_amount' => 30,
        'range_start' => now()->format('Y-m-d'),
        'stats_sort' => 'reg',
    ]));

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('B')
        ->and((int) $response->json('data.0.reg'))->toBe(1);
});

test('changing the property dimension persists the selection in the user settings', function (): void {

    $user = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $user->recordCustomProperties(['ab_test' => 'B']);

    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');

    // initial fetch without group_type must not persist anything
    $this->getJson(route('stats.grouped', [
        'project_id' => $this->project->id,
        'stats_type' => 'property',
        'property_name_id' => $nameId,
    ]))->assertOk();

    expect($this->user->refresh()->settings['selectedGroupTypes'][$this->project->id]['property'] ?? null)->toBeNull();

    // an explicit dimension change (group_type=property) persists the selection
    $this->getJson(route('stats.grouped', [
        'project_id' => $this->project->id,
        'stats_type' => 'property',
        'property_name_id' => $nameId,
        'group_type' => 'property',
    ]))->assertOk();

    expect($this->user->refresh()->settings['selectedGroupTypes'][$this->project->uuid]['property'])->toBe($nameId);
});

test('the grouped endpoint rejects a property name from another team', function (): void {
    $response = $this->getJson(route('stats.grouped', [
        'project_id' => $this->project->id,
        'stats_type' => 'property',
        'property_name_id' => 999999,
    ]));

    $response->assertStatus(422);
});

test('property dimensions are deduplicated across projects and scoped per project', function (): void {
    $project2 = Project::factory()->create(['team_id' => $this->project->team_id]);

    $user1 = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $user2 = StatsUser::factory()->create(['project_id' => $project2->id, 'time' => now()->subDay()]);

    // both projects track "ab_test", only project 2 tracks "company"
    $user1->recordCustomProperties(['ab_test' => 'A']);
    $user2->recordCustomProperties(['ab_test' => 'B', 'company' => 'Acme Inc']);

    // "All" view: one tab per name, no duplicates
    expect(StatsCustomPropertyService::getPropertyDimensions(propertyFilters(null))->pluck('name')->all())->toBe(['Ab Test', 'Company']);

    // per project: only names actually recorded for that project
    expect(StatsCustomPropertyService::getPropertyDimensions(propertyFilters($this->project))->pluck('name')->all())->toBe(['Ab Test'])
        ->and(StatsCustomPropertyService::getPropertyDimensions(propertyFilters($project2))->pluck('name')->all())->toBe(['Ab Test', 'Company']);

    // "All" aggregation merges values from both projects into the same buckets
    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');
    $allFilters = new StatsFilterDto(
        projectId: 0,
        rangeAmount: 30,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
    );

    $data = resolve(StatsAggregationsService::class)->getAggregatedPropertyData($allFilters, $nameId, 'reg', 10);

    expect($data->pluck('name')->sort()->values()->all())->toBe(['A', 'B']);
});

test('property dimensions stay visible regardless of the selected period', function (): void {

    // "legacy" is only bound to a user registered 60 days ago
    $oldUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(60)]);
    $oldUser->recordCustomProperties(['legacy' => 'yes']);

    // "ab_test" is bound to a visitor tracked yesterday
    $visitor = StatsVisitor::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $visitor->recordCustomProperties(['ab_test' => 'B']);

    // Even a short 30-day range keeps both tabs: a dimension does not vanish
    // just because its only data point falls outside the current period.
    expect(StatsCustomPropertyService::getPropertyDimensions(propertyFilters($this->project))->pluck('name')->all())->toBe(['Ab Test', 'Legacy']);

    // A wider range shows the same set.
    expect(StatsCustomPropertyService::getPropertyDimensions(propertyFilters($this->project, 90))->pluck('name')->all())->toBe(['Ab Test', 'Legacy']);
});

test('property dimensions exclude names that were never recorded', function (): void {

    // A name lookup row with no pivot entries (e.g. created, then all its
    // entities deleted) must not produce a tab.
    StatsCustomPropertyName::create([
        'project_id' => $this->project->id,
        'name' => 'orphan',
    ]);

    expect(StatsCustomPropertyService::getPropertyDimensions(propertyFilters($this->project))->pluck('name')->all())->toBe([]);
});

test('the property value filter narrows grouped stats to matching entities', function (): void {

    // track_source is set explicitly: the factory may randomize it to null,
    // and users without a source do not appear in any source bucket
    $proUsers = StatsUser::factory(2)->create(['project_id' => $this->project->id, 'time' => now()->subDay(), 'track_source' => 'newsletter']);
    $freeUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay(), 'track_source' => 'newsletter']);

    foreach ($proUsers as $proUser) {
        $proUser->recordCustomProperties(['ab_test' => 'B']);
    }
    $freeUser->recordCustomProperties(['ab_test' => 'A']);

    $proValueId = StatsCustomPropertyValue::where('value', 'B')->value('id');

    $filters = new StatsFilterDto(
        projectId: $this->project->id,
        rangeAmount: 30,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
        propertyValue: $proValueId,
    );

    $data = resolve(StatsAggregationsService::class)->getAggregatedGroupedData($filters, 'track_source', 'reg', 10);

    // only the 2 "pro" users survive the filter, the "free" user is excluded
    expect((int) $data->sum('reg'))->toBe(2);
});

test('an active property value filter narrows the property group itself', function (): void {

    $proUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $freeUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);

    $proUser->recordCustomProperties(['ab_test' => 'B', 'company' => 'acme']);
    $freeUser->recordCustomProperties(['ab_test' => 'A', 'company' => 'beta']);

    $proValueId = StatsCustomPropertyValue::where('value', 'B')->value('id');
    $abTestNameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');
    $companyNameId = StatsCustomPropertyName::where('name', 'company')->value('id');

    $filters = new StatsFilterDto(
        projectId: $this->project->id,
        rangeAmount: 30,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
        propertyValue: $proValueId,
    );

    $aggregationsService = resolve(StatsAggregationsService::class);

    // same dimension: only the selected value remains
    expect($aggregationsService->getAggregatedPropertyData($filters, $abTestNameId, 'reg', 10)->pluck('name')->all())->toBe(['B']);

    // other dimension: only values bound to the filtered entities remain
    expect($aggregationsService->getAggregatedPropertyData($filters, $companyNameId, 'reg', 10)->pluck('name')->all())->toBe(['acme']);
});

test('an active property value filter narrows the triggered counts of the events group', function (): void {

    $proVisitor = StatsVisitor::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $freeVisitor = StatsVisitor::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);

    $proVisitor->recordCustomProperties(['ab_test' => 'B']);
    $freeVisitor->recordCustomProperties(['ab_test' => 'A']);

    $eventNameA = StatsCustomEventName::factory()->create(['team_id' => $this->project->team_id, 'project_id' => $this->project->id, 'name' => 'signup_clicked']);
    $eventNameB = StatsCustomEventName::factory()->create(['team_id' => $this->project->team_id, 'project_id' => $this->project->id, 'name' => 'demo_booked']);

    StatsCustomEvent::factory()->create([
        'team_id' => $this->project->team_id,
        'project_id' => $this->project->id,
        'stats_custom_event_name_id' => $eventNameA->id,
        'stats_user_id' => null,
        'stats_visitor_id' => $proVisitor->id,
        'time' => now()->subDay(),
        'time_utc' => now()->subDay(),
    ]);
    StatsCustomEvent::factory()->create([
        'team_id' => $this->project->team_id,
        'project_id' => $this->project->id,
        'stats_custom_event_name_id' => $eventNameB->id,
        'stats_user_id' => null,
        'stats_visitor_id' => $freeVisitor->id,
        'time' => now()->subDay(),
        'time_utc' => now()->subDay(),
    ]);

    $proValueId = StatsCustomPropertyValue::where('value', 'B')->value('id');

    $filters = new StatsFilterDto(
        projectId: $this->project->id,
        rangeAmount: 30,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
        propertyValue: $proValueId,
    );

    $grouped = resolve(StatsAggregationsService::class)
        ->getAggregatedGroupedData($filters, 'custom_event_name', 'name', 10)
        ->keyBy('name');

    // only the event of the "pro" visitor counts as triggered
    expect((int) $grouped['signup_clicked']['triggered'])->toBe(1)
        ->and((int) $grouped['demo_booked']['triggered'])->toBe(0)
        // total visitors (CR denominator) narrows to the "pro" visitor too
        ->and((int) $grouped['signup_clicked']['visitors'])->toBe(1);
});

test('an active property value filter narrows the chart aggregations', function (): void {

    $proUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $freeUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $proVisitor = StatsVisitor::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $freeVisitor = StatsVisitor::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);

    $proUser->recordCustomProperties(['ab_test' => 'B']);
    $freeUser->recordCustomProperties(['ab_test' => 'A']);
    $proVisitor->recordCustomProperties(['ab_test' => 'B']);
    $freeVisitor->recordCustomProperties(['ab_test' => 'A']);

    $proValueId = StatsCustomPropertyValue::where('value', 'B')->value('id');

    $filters = new StatsFilterDto(
        projectId: $this->project->id,
        rangeAmount: 7,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
        propertyValue: $proValueId,
    );

    $data = resolve(StatsAggregationsService::class)->getAggregatedData($filters);

    // only the "pro" visitor and the "pro" registration survive in the charts
    expect((int) $data->sum('pd_visitor'))->toBe(1)
        ->and((int) $data->sum('pd_reg'))->toBe(1);
});

test('the lifetime toggle persists per project in the user settings', function (): void {

    $this->postJson(route('user-settings.property-lifetime'), [
        'project_id' => $this->project->id,
        'lifetime' => true,
    ])->assertOk();

    expect($this->user->refresh()->settings['propertyLifetime'][$this->project->id])->toBeTrue();

    $this->postJson(route('user-settings.property-lifetime'), [
        'project_id' => $this->project->id,
        'lifetime' => false,
    ])->assertOk();

    expect($this->user->refresh()->settings['propertyLifetime'][$this->project->id])->toBeFalse();
});

test('lifetime mode ignores the date range while time-range mode respects it', function (): void {

    // "pro" user registered long before the selected window, "free" inside it
    $oldUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(60)]);
    $recentUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);

    $oldUser->recordCustomProperties(['ab_test' => 'B']);
    $recentUser->recordCustomProperties(['ab_test' => 'A']);

    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');
    $aggregationsService = resolve(StatsAggregationsService::class);

    // 7-day range: time-range mode only sees the recent "free" user
    $timeRange = $aggregationsService
        ->getAggregatedPropertyData(propertyFilters($this->project, 7), $nameId, 'reg', 10, false);

    expect($timeRange->pluck('name')->all())->toBe(['A']);

    // lifetime mode shows every entity grouped by its current value, regardless of date
    $lifetime = $aggregationsService
        ->getAggregatedPropertyData(propertyFilters($this->project, 7), $nameId, 'reg', 10, true)
        ->keyBy('name');

    expect($lifetime->keys()->sort()->values()->all())->toBe(['A', 'B'])
        ->and((int) $lifetime['B']['reg'])->toBe(1)
        ->and((int) $lifetime['A']['reg'])->toBe(1);
});

test('lifetime mode sums revenue across all time', function (): void {

    $proUser = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(60)]);
    $proUser->recordCustomProperties(['ab_test' => 'B']);

    StatsPayment::factory()->create([
        'project_id' => $this->project->id,
        'stats_user_id' => $proUser->id,
        'net' => 100,
        'currency' => 'EUR',
        'time' => now()->subDays(60),
    ]);

    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');
    $aggregationsService = resolve(StatsAggregationsService::class);

    // payment is out of the 7-day window: time-range mode rejects the empty bucket
    expect($aggregationsService->getAggregatedPropertyData(propertyFilters($this->project, 7), $nameId, 'reg', 10, false)->all())->toBe([]);

    $lifetime = $aggregationsService
        ->getAggregatedPropertyData(propertyFilters($this->project, 7), $nameId, 'reg', 10, true)
        ->keyBy('name');

    expect((float) $lifetime['B']['net'])->toBe(100.0);
});

test('cross-filters still narrow the lifetime result', function (): void {

    // all registered outside the 7-day window so only lifetime mode returns them
    $newsletterPro = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(60), 'track_source' => 'newsletter']);
    $newsletterFree = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(60), 'track_source' => 'newsletter']);
    $otherPro = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDays(60), 'track_source' => 'twitter']);

    $newsletterPro->recordCustomProperties(['ab_test' => 'B']);
    $newsletterFree->recordCustomProperties(['ab_test' => 'A']);
    $otherPro->recordCustomProperties(['ab_test' => 'B']);

    $nameId = StatsCustomPropertyName::where('name', 'ab_test')->value('id');
    $newsletterSourceId = StatsTrackSource::where('name', 'newsletter')->value('id');

    $filters = new StatsFilterDto(
        projectId: $this->project->id,
        rangeAmount: 7,
        rangeType: StatsRangeType::DAY,
        rangeStart: now(),
        comparison: '0',
        trackSource: $newsletterSourceId,
    );

    $lifetime = resolve(StatsAggregationsService::class)
        ->getAggregatedPropertyData($filters, $nameId, 'reg', 10, true)
        ->keyBy('name');

    // only the two newsletter users survive: pro=1 (newsletter), free=1, twitter "pro" excluded
    expect($lifetime->keys()->sort()->values()->all())->toBe(['A', 'B'])
        ->and((int) $lifetime['B']['reg'])->toBe(1)
        ->and((int) $lifetime['A']['reg'])->toBe(1);
});

test('values with no activity in the period are rejected', function (): void {

    $user = StatsUser::factory()->create(['project_id' => $this->project->id, 'time' => now()->subDay()]);
    $user->recordCustomProperties(['plan' => 'starter']);

    // "enterprise" value exists in the lookup but no entity uses it in range
    $nameId = StatsCustomPropertyName::where('name', 'plan')->value('id');
    StatsCustomPropertyValue::create([
        'project_id' => $this->project->id,
        'stats_custom_property_name_id' => $nameId,
        'value' => 'enterprise',
    ]);

    $data = resolve(StatsAggregationsService::class)
        ->getAggregatedPropertyData(propertyFilters($this->project), $nameId, 'reg', 10);

    expect($data->pluck('name')->all())->toBe(['starter']);
});
