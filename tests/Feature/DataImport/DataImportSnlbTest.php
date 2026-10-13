<?php

use App\Enums\DataImportType;
use App\Imports\StatsUserImport;
use App\Models\DataImport;
use App\Models\Project;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Models\User;
use App\Services\SimulatedLoginService;
use App\Validators\BaseStatsValidator;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->team->update(['timezone' => 'UTC']);
    $this->project = Project::factory()->create(['team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

function makeSnlbImporter($team, $project, int $rowsMax = 10): StatsUserImport
{
    $dataImport = new DataImport;
    $dataImport->type = DataImportType::USERS;
    $dataImport->rows_max = $rowsMax;
    $dataImport->team_id = $team->id;
    $dataImport->project_id = $project->id;
    $dataImport->setRelation('team', $team);

    return new StatsUserImport($dataImport);
}

function makeSnlbDataImport($team, $project, int $rowsMax = 10): DataImport
{
    $dataImport = new DataImport;
    $dataImport->type = DataImportType::USERS;
    $dataImport->rows_max = $rowsMax;
    $dataImport->team_id = $team->id;
    $dataImport->project_id = $project->id;
    $dataImport->created_by = auth()->id();
    $dataImport->save();
    $dataImport->setRelation('team', $team);

    return $dataImport;
}

function buildSnlbRows($test, CarbonImmutable $registeredAt, CarbonImmutable $lastKnownActivity): array
{
    return resolve(SimulatedLoginService::class)->buildLoginRows(
        '1', $test->team->id, $test->project->id, $registeredAt, $lastKnownActivity, 'UTC'
    );
}

test('generates a natural weekly login series from registration to last known activity', function (): void {
    $registeredAt = CarbonImmutable::parse('2025-01-01 10:00:00', 'UTC');
    $lastKnownActivity = CarbonImmutable::parse('2025-03-12 15:30:00', 'UTC'); // 70 days later

    $rows = buildSnlbRows($this, $registeredAt, $lastKnownActivity);

    expect($rows[0]['time'])->toBe('2025-01-01 10:00:00')
        ->and(end($rows)['time'])->toBe('2025-03-12 15:30:00')
        ->and(count($rows))->toBeGreaterThanOrEqual(10)
        ->and(count($rows))->toBeLessThanOrEqual(20);

    foreach ($rows as $i => $row) {
        expect($row['counter'])->toBe($i + 1)
            ->and($row['time'])->not->toBeEmpty()
            ->and($row['time_utc'])->not->toBeEmpty();

        if ($i > 0) {
            $gapInDays = CarbonImmutable::parse($rows[$i - 1]['time'])->startOfDay()
                ->diffInDays(CarbonImmutable::parse($row['time'])->startOfDay());

            expect($gapInDays)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(7);
        }
    }
});

test('generates a single registration-day login when last known activity is not after registration', function (): void {
    $registeredAt = CarbonImmutable::parse('2025-01-01 10:00:00', 'UTC');

    $rows = buildSnlbRows($this, $registeredAt, $registeredAt->subDay());

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['counter'])->toBe(1)
        ->and($rows[0]['time'])->toBe('2025-01-01 10:00:00');
});

test('clamps a future last known activity to now', function (): void {
    $this->travelTo(CarbonImmutable::parse('2025-06-01 12:00:00', 'UTC'));

    $rows = buildSnlbRows(
        $this,
        CarbonImmutable::parse('2025-05-20 10:00:00', 'UTC'),
        CarbonImmutable::parse('2025-07-01 10:00:00', 'UTC'),
    );

    expect(end($rows)['time'])->toBe('2025-06-01 12:00:00');
});

test('caps the generated series for absurd activity spans', function (): void {
    $rows = buildSnlbRows(
        $this,
        CarbonImmutable::parse('1970-01-01 00:00:00', 'UTC'),
        now()->toImmutable(),
    );

    expect($rows)->toHaveCount(SimulatedLoginService::MAX_LOGINS_PER_USER);
});

test('import strips last_known_activity from the user model and buffers logins', function (): void {
    $importer = makeSnlbImporter($this->team, $this->project);

    $model = $importer->model([
        'id' => 1,
        'time' => '2025-01-01 10:00:00',
        'last_known_activity' => '2025-02-01 10:00:00',
    ]);

    expect($model)->toBeInstanceOf(StatsUser::class)
        ->and($model->getAttributes())->not->toHaveKey('last_known_activity')
        ->and($importer->generatedLoginsCount)->toBeGreaterThan(0);
});

test('import buffers nothing without a last_known_activity value', function (): void {
    $importer = makeSnlbImporter($this->team, $this->project);

    $importer->model(['id' => 1, 'time' => '2025-01-01 10:00:00']);
    $importer->model(['id' => 2, 'time' => '2025-01-01 10:00:00', 'last_known_activity' => null]);

    expect($importer->generatedLoginsCount)->toBe(0);
});

test('flush skips users that already have logins and inserts for fresh ones', function (): void {
    StatsUser::create(['id' => 1, 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2025-01-01 10:00:00']);
    StatsUser::create(['id' => 2, 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2025-01-01 10:00:00']);
    StatsLogin::create(['stats_user_id' => 1, 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2025-01-02 10:00:00']);

    $importer = makeSnlbImporter($this->team, $this->project);
    $importer->model(['id' => 1, 'time' => '2025-01-01 10:00:00', 'last_known_activity' => '2025-02-01 10:00:00']);
    $importer->model(['id' => 2, 'time' => '2025-01-01 10:00:00', 'last_known_activity' => '2025-02-01 10:00:00']);

    $importer->flushPendingLogins();

    $freshUserLogins = StatsLogin::where('stats_user_id', 2)->count();

    expect(StatsLogin::where('stats_user_id', 1)->count())->toBe(1)
        ->and($freshUserLogins)->toBeGreaterThan(0)
        ->and($importer->generatedLoginsCount)->toBe($freshUserLogins);
});

test('generated logins do not count against the monthly event limit', function (): void {
    $limit = $this->team->owner->sparkPlan()->options['Events'];
    $this->team->update(['events_count_monthly' => $limit - 2]);
    $this->team->refresh();

    $importer = makeSnlbImporter($this->team, $this->project);

    $importer->model(['id' => 1, 'time' => now()->subYear()->format('Y-m-d H:i:s'), 'last_known_activity' => now()->subDay()->format('Y-m-d H:i:s')]);
    $importer->model(['id' => 2, 'time' => now()->subYear()->format('Y-m-d H:i:s'), 'last_known_activity' => now()->subDay()->format('Y-m-d H:i:s')]);

    // ~2x52 buffered logins, but only the 2 file rows count against the remaining 2 events
    expect($importer->generatedLoginsCount)->toBeGreaterThan(2);
});

test('validateImportFields accepts the last_known_activity header for user imports', function (): void {
    $dataImport = new DataImport;
    $dataImport->type = DataImportType::USERS;
    $dataImport->team_id = $this->team->id;
    $dataImport->project_id = $this->project->id;
    $dataImport->setRelation('team', $this->team);

    resolve(BaseStatsValidator::class)->validateImportFields($dataImport, ['id', 'time', 'last_known_activity']);

    expect(fn () => resolve(BaseStatsValidator::class)->validateImportFields($dataImport, ['id', 'time', 'not_a_field']))
        ->toThrow(ValidationException::class);
});

test('a last_known_activity before the registration time fails row validation', function (): void {
    $csv = "id,time,last_known_activity\n1,2025-02-01 10:00:00,2025-01-01 10:00:00\n";
    $path = tempnam(sys_get_temp_dir(), 'snlb_').'.csv';
    file_put_contents($path, $csv);

    $importer = makeSnlbImporter($this->team, $this->project, rowsMax: 1);

    try {
        expect(fn () => Excel::import($importer, $path))->toThrow(ExcelValidationException::class);
    } finally {
        unlink($path);
    }
});

test('a full user import generates logins and rollback removes them via cascade', function (): void {
    $csv = "id,time,last_known_activity\n1,2025-01-01 10:00:00,2025-03-01 10:00:00\n2,2025-01-05 08:00:00,\n";
    $path = tempnam(sys_get_temp_dir(), 'snlb_').'.csv';
    file_put_contents($path, $csv);

    $dataImport = makeSnlbDataImport($this->team, $this->project, rowsMax: 2);
    $importer = new StatsUserImport($dataImport);

    try {
        Excel::import($importer, $path);
    } finally {
        unlink($path);
    }

    $firstUserLogins = StatsLogin::where('stats_user_id', 1)->orderBy('time')->get();

    expect(StatsUser::count())->toBe(2)
        ->and($firstUserLogins->first()->time->format('Y-m-d H:i:s'))->toBe('2025-01-01 10:00:00')
        ->and($firstUserLogins->last()->time->format('Y-m-d H:i:s'))->toBe('2025-03-01 10:00:00')
        ->and($firstUserLogins->count())->toBeGreaterThanOrEqual(9)
        ->and(StatsLogin::where('stats_user_id', 2)->count())->toBe(0);

    // same delete as DataImportController::rollback()
    StatsUser::where('data_import_id', $dataImport->id)->delete();

    expect(StatsUser::count())->toBe(0)
        ->and(StatsLogin::count())->toBe(0);
});

test('imports users with uuid ids and rolls back exactly this import', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    // a pre-existing user (e.g. live-tracked) that must survive the rollback
    StatsUser::create(['id' => 'aaaaaaaa-0000-0000-0000-000000000000', 'team_id' => $this->team->id, 'project_id' => $this->project->id, 'time' => '2025-01-01 10:00:00']);

    $csv = "id,time\n{$uuid},2025-01-02 10:00:00\n";
    $path = tempnam(sys_get_temp_dir(), 'uuid_').'.csv';
    file_put_contents($path, $csv);

    $dataImport = makeSnlbDataImport($this->team, $this->project, rowsMax: 1);
    $importer = new StatsUserImport($dataImport);

    try {
        Excel::import($importer, $path);
    } finally {
        unlink($path);
    }

    expect(StatsUser::where('id', $uuid)->where('project_id', $this->project->id)->exists())->toBeTrue();

    StatsUser::where('data_import_id', $dataImport->id)->delete();

    expect(StatsUser::where('id', $uuid)->exists())->toBeFalse()
        ->and(StatsUser::where('id', 'aaaaaaaa-0000-0000-0000-000000000000')->exists())->toBeTrue();
});
