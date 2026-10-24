<?php

namespace App\Console\Commands;

use App\Services\StatsAggregationsService;
use App\Services\StatsService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckLoginsAndPaymentsCounter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-logins-and-payments-counter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check logins and payments counter';

    /**
     * The only counter value per table the cached period KPIs depend on: pd_pu/lt_pu/pd_napu
     * count payments with counter = 1, pd_nau/pd_tau/lt_tau count logins with the nau counter.
     *
     * @var array<string, int>
     */
    protected array $tables = [
        'stats_logins' => StatsService::NAU_LOGIN_COUNTER,
        'stats_payments' => 1,
    ];

    /**
     * Execute the console command.
     */
    public function handle(StatsAggregationsService $statsAggregationsService): void
    {
        foreach ($this->tables as $table => $kpiCounter) {
            // stats_payments.id is the customer's payment id and only unique per project,
            // so the join must include project_id or rows get the rank of a foreign project.
            // The UPDATE runs as a data-modifying CTE, the outer SELECT reports per project the
            // earliest row whose change moves it into or out of the cached KPIs.
            $affectedProjects = DB::select("
                WITH ranked AS (
                    SELECT id, project_id, ROW_NUMBER() OVER (PARTITION BY project_id, stats_user_id ORDER BY time, id) AS rn
                    FROM $table
                ),
                changed AS (
                    SELECT t.id, t.project_id, t.team_id, t.time, t.counter AS old_counter, ranked.rn AS new_counter
                    FROM $table t
                    JOIN ranked ON ranked.id = t.id AND ranked.project_id = t.project_id
                    WHERE t.counter IS DISTINCT FROM ranked.rn
                ),
                updated AS (
                    UPDATE $table
                    SET counter = changed.new_counter
                    FROM changed
                    WHERE $table.id = changed.id AND $table.project_id = changed.project_id
                )
                SELECT team_id, project_id, MIN(time) AS since
                FROM changed
                WHERE (old_counter = ?) IS DISTINCT FROM (new_counter = ?)
                GROUP BY team_id, project_id
            ", [$kpiCounter, $kpiCounter]);

            // The raw update bypasses the StatsObserver, so the affected cached periods are
            // dropped here. Earlier buckets and the MRR/engagement series stay cached.
            foreach ($affectedProjects as $affected) {
                $statsAggregationsService->invalidatePeriodsSince(
                    $affected->team_id,
                    $affected->project_id,
                    CarbonImmutable::parse($affected->since),
                );
            }
        }
    }
}
