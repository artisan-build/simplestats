<?php

namespace App\Console\Commands;

use App\Models\StatsTrackReferer;
use App\Models\StatsTrackSource;
use App\Models\StatsUser;
use App\Models\Team;
use App\Services\RefererService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExtractReferersFromSources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-referers-from-sources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
                StatsTrackSource::where('team_id', $team->id)->orderBy('id')->get()->each(function (StatsTrackSource $source) use ($refererService) {
                    $sourceName = $source->name;

                    // if source contains a dot, we assume it's a referer...
                    if (str($sourceName)->contains('.')) {
                        $referer = $refererService->getTopReferer($sourceName);

                        StatsTrackReferer::unguard(); // make team and project fillable
                        $refererStat = StatsTrackReferer::firstOrCreate([
                            'name' => $referer,
                            'team_id' => $source->team_id,
                            'project_id' => $source->project_id,
                        ]);

                        StatsUser::where([
                            'team_id' => $source->team_id,
                            'project_id' => $source->project_id,
                            'stats_track_source_id' => $source->id,
                        ])->update([
                            'stats_track_source_id' => null,
                            'stats_track_referer_id' => $refererStat->id,
                        ]);

                        $source->delete();
                    }
                });
            } catch (\Exception $e) {
                DB::rollBack();
            }

            DB::commit();
        });
    }
}
