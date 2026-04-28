<?php

namespace App\Console\Commands;

use App\Models\StatsAggregation;
use App\Models\StatsTrackReferer;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use App\Services\RefererService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UnifyTrackReferer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:unify-track-referer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge stats_track_referers entries that drift from their canonical form (protocol, www, subdomain folds, aliases).';

    /**
     * Execute the console command.
     */
    public function handle(RefererService $refererService): void
    {
        Team::with('owner')->get()->each(function ($team) use ($refererService) {
            if (empty($team->owner)) {
                return;
            }

            DB::beginTransaction();

            try {
                StatsTrackReferer::where('team_id', $team->id)
                    ->get()
                    ->each(function (StatsTrackReferer $duplicate) use ($refererService) {
                        $canonical = $refererService->getTopReferer($duplicate->name);

                        if ($canonical === $duplicate->name) {
                            return;
                        }

                        StatsTrackReferer::unguard();
                        $target = StatsTrackReferer::firstOrCreate([
                            'team_id' => $duplicate->team_id,
                            'name' => $canonical,
                        ]);

                        if ($target->id === $duplicate->id) {
                            return;
                        }

                        StatsUser::where([
                            'team_id' => $duplicate->team_id,
                            'stats_track_referer_id' => $duplicate->id,
                        ])->update([
                            'stats_track_referer_id' => $target->id,
                        ]);

                        StatsVisitor::where([
                            'team_id' => $duplicate->team_id,
                            'stats_track_referer_id' => $duplicate->id,
                        ])->update([
                            'stats_track_referer_id' => $target->id,
                        ]);

                        $duplicate->delete();
                    });
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Team {$team->id}: {$e->getMessage()}");

                return;
            }

            DB::commit();
        });

        StatsAggregation::truncate();
    }
}
