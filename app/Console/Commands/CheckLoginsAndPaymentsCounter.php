<?php

namespace App\Console\Commands;

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

    protected array $tables = [
        'stats_logins',
        'stats_payments',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach ($this->tables as $table) {
            if (config('database.default') === 'pgsql') {
                DB::statement("
                    UPDATE stats_logins
                    SET counter = ranked.rn
                    FROM (
                        SELECT id, ROW_NUMBER() OVER (PARTITION BY instance_id, stats_user_id ORDER BY time) AS rn
                        FROM stats_logins
                    ) AS ranked
                    WHERE stats_logins.id = ranked.id;
                ");
            } else {
                DB::statement("
                UPDATE stats_logins AS s
                JOIN (
                    SELECT id,
                           ROW_NUMBER() OVER (PARTITION BY instance_id, stats_user_id ORDER BY time) AS rn
                    FROM stats_logins
                ) AS ranked
                ON s.id = ranked.id
                SET s.counter = ranked.rn;
            ");
            }
        }
    }
}
