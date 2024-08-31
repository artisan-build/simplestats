<?php

namespace App\Console\Commands;

use App\Models\StatsAggregation;
use App\Models\StatsTrackReferer;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Team::with('owner')->get()->each(function ($team) {
            if (empty($team->owner)) {
                return;
            }

            DB::beginTransaction();

            try {
                StatsTrackReferer::where('team_id', $team->id)->get()->each(function (StatsTrackReferer $referer) {
                    $refererName = $referer->name;

                    $refererWithWWW = StatsTrackReferer::where([
                        'team_id' => $referer->team_id,
                        'project_id' => $referer->project_id,
                        'name' => 'www.'.$refererName,
                    ])->first();

                    if ($refererWithWWW) {
                        StatsUser::where([
                            'team_id' => $refererWithWWW->team_id,
                            'project_id' => $refererWithWWW->project_id,
                            'stats_track_referer_id' => $refererWithWWW->id,
                        ])->update([
                            'stats_track_referer_id' => $referer->id,
                        ]);

                        StatsVisitor::where([
                            'team_id' => $refererWithWWW->team_id,
                            'project_id' => $refererWithWWW->project_id,
                            'stats_track_referer_id' => $refererWithWWW->id,
                        ])->update([
                            'stats_track_referer_id' => $referer->id,
                        ]);

                        $refererWithWWW->delete();
                    }
                });
            } catch (\Exception $e) {
                DB::rollBack();
            }

            DB::commit();
        });

        // remove all sub string "www." from entries where only a single www entry exists...
        DB::table((new StatsTrackReferer())->getTable())
            ->where('name', 'LIKE', 'www.%')
            ->where('name', '!=', 'www.google.com')
            ->update([
                'name' => DB::raw('SUBSTRING(name, 5)'),
            ]);

        StatsAggregation::truncate();
    }
}
