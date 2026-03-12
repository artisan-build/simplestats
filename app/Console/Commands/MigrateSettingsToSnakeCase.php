<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrateSettingsToSnakeCase extends Command
{
    protected $signature = 'app:migrate-settings-to-snake-case';

    protected $description = 'Migrate user settings keys from camelCase to snake_case (statsFilters and selectedGroupTypes)';

    public function handle(): int
    {
        $updated = 0;

        User::whereNotNull('settings')
            ->chunkById(100, function ($users) use (&$updated) {
                foreach ($users as $user) {
                    $settings = $user->settings;
                    $changed = false;

                    // Migrate statsFilters keys (per team)
                    if (! empty($settings['statsFilters'])) {
                        foreach ($settings['statsFilters'] as $teamId => $filters) {
                            $converted = collect($filters)
                                ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => $value])
                                ->toArray();

                            if ($converted !== $filters) {
                                $settings['statsFilters'][$teamId] = $converted;
                                $changed = true;
                            }
                        }
                    }

                    // Migrate selectedGroupTypes values (e.g. trackSource -> track_source)
                    if (! empty($settings['selectedGroupTypes'])) {
                        foreach ($settings['selectedGroupTypes'] as $projectId => $groupTypes) {
                            foreach ($groupTypes as $groupType => $value) {
                                $snakeValue = Str::snake($value);
                                if ($snakeValue !== $value) {
                                    $settings['selectedGroupTypes'][$projectId][$groupType] = $snakeValue;
                                    $changed = true;
                                }
                            }
                        }
                    }

                    if ($changed) {
                        $user->update(['settings' => $settings]);
                        $updated++;
                    }
                }
            });

        $this->info("Migrated settings for {$updated} users.");

        return self::SUCCESS;
    }
}
