<?php

namespace App\Imports;

use App\Enums\StatsRangeType;
use App\Models\DataImport;
use App\Models\StatsLogin;
use App\Models\StatsUser;
use App\Services\SimulatedLoginService;
use App\Validators\StatsUserValidator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Events\AfterBatch;

class StatsUserImport extends BaseStatsImport implements WithEvents, WithUpserts
{
    use RegistersEventListeners;

    /** @var array<string, list<array<string, mixed>>> stats_user_id => pending simulated login rows */
    protected array $pendingLogins = [];

    public int $generatedLoginsCount = 0;

    public function __construct(DataImport $dataImport)
    {
        parent::__construct(
            $dataImport,
            StatsUser::class,
            app(StatsUserValidator::class)
        );
    }

    public function uniqueBy(): array
    {
        return ['id', 'project_id'];
    }

    /**
     * last_known_activity (SNLB) is an import-only column, so it lives here and not in
     * StatsUserValidator, which is also the API ingest surface (StatsUserRequest).
     * The header check (validateImportFields) reads these importer rules.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // the '*.' prefix is required: RowValidator validates rows keyed as {index}.field,
            // so a bare 'time' would resolve against the root data and fail every row
            'last_known_activity' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s P', 'after_or_equal:*.time'],
        ]);
    }

    public function prepareForValidation($data, $index): array
    {
        if (! empty($data['last_known_activity'])) {
            $data['last_known_activity'] = CarbonImmutable::parse($data['last_known_activity'])->tz('UTC')->format(StatsRangeType::DATE_FORMAT.' P');
        }

        return parent::prepareForValidation($data, $index);
    }

    public function model(array $row)
    {
        // stats_users has no such column, so strip it before the parent builds the model
        $lastKnownActivity = Arr::pull($row, 'last_known_activity');

        $model = parent::model($row);

        if (! empty($lastKnownActivity)) {
            $userId = (string) $row['id'];

            $loginRows = app(SimulatedLoginService::class)->buildLoginRows(
                $userId,
                $this->dataImport->team_id,
                $this->dataImport->project_id,
                CarbonImmutable::parse($row['time']),
                CarbonImmutable::parse($lastKnownActivity),
                $this->dataImport->team->timezone,
            );

            // a duplicate id within the file replaces its previous series
            $this->generatedLoginsCount -= count($this->pendingLogins[$userId] ?? []);
            $this->pendingLogins[$userId] = $loginRows;
            $this->generatedLoginsCount += count($loginRows);
        }

        return $model;
    }

    /**
     * Fired right after each batch of users has been persisted, so the
     * stats_logins FK on [stats_user_id, project_id] is always satisfiable.
     */
    public function afterBatch(AfterBatch $event): void
    {
        $this->flushPendingLogins();
    }

    public function flushPendingLogins(): void
    {
        if (empty($this->pendingLogins)) {
            return;
        }

        // users that already have logins keep them (re-import / already live-tracked)
        $skipUserIds = StatsLogin::where('project_id', $this->dataImport->project_id)
            ->whereIn('stats_user_id', array_keys($this->pendingLogins))
            ->distinct()
            ->pluck('stats_user_id')
            ->all();

        foreach ($skipUserIds as $userId) {
            $this->generatedLoginsCount -= count($this->pendingLogins[$userId]);
            unset($this->pendingLogins[$userId]);
        }

        collect($this->pendingLogins)
            ->flatten(1)
            ->chunk(self::BATCH_CHUNK_SIZE)
            ->each(fn ($loginRows) => StatsLogin::insert($loginRows->values()->all()));

        $this->pendingLogins = [];
    }
}
