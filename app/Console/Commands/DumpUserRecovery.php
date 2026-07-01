<?php

namespace App\Console\Commands;

use App\Services\UserRecoveryDumpService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DumpUserRecovery extends Command
{
    protected $signature = 'app:dump-user-recovery
        {user : Email or ID of the user to recover}
        {--output= : Target path for the generated .sql file}
        {--with-billing : Include the Paddle billing tables (customers, subscriptions, transactions)}
        {--on-conflict-do-nothing : Append ON CONFLICT DO NOTHING to every INSERT}';

    protected $description = 'Dumps all data belonging to a user from the current (backup) database into a replayable INSERT SQL file';

    public function handle(UserRecoveryDumpService $service): int
    {
        $result = $service->dump(
            $this->argument('user'),
            (bool) $this->option('with-billing'),
            (bool) $this->option('on-conflict-do-nothing'),
        );

        $path = $this->option('output') ?: base_path('storage/app/recovery-user-'.$result['user_id'].'.sql');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $result['sql']);

        $this->info("Recovery dump for {$result['email']} (id {$result['user_id']}) written to:");
        $this->line('  '.$path);
        $this->newLine();

        $this->table(['Table', 'Rows'], collect($result['summary'])
            ->filter(fn (int $count) => $count > 0)
            ->map(fn (int $count, string $table) => [$table, $count])
            ->values()
            ->all());

        return self::SUCCESS;
    }
}
