<?php

namespace App\Console\Commands;

use App\Models\StatsAggregation;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use App\Services\StatsService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeStatsTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:merge-stats-types';

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
        Team::with('owner')->get()->each(function ($team): void {
            if (empty($team->owner)) {
                return;
            }

            DB::beginTransaction();

            try {
                StatsService::getStatsTypes()->each(function ($types) use ($team): void {
                    collect($types)->each(function (array $statsType) use ($team): void {
                        $statsTypeModel = $statsType['model'];

                        $statsTypeModel::where('team_id', $team->id)
                            ->get()->unique('name')->each(function ($statsModel) use ($statsTypeModel, $statsType): void {
                                $statsTypeModel::where([
                                    'team_id' => $statsModel->team_id,
                                    'name' => $statsModel->name,
                                ])->get()->each(function ($statsModelWithSameName) use ($statsModel, $statsType): void {
                                    if ($statsModel->id != $statsModelWithSameName->id) {
                                        $statsTrackIdColumn = 'stats_'.$statsType['type'].'_id';

                                        StatsUser::where([
                                            'team_id' => $statsModelWithSameName->team_id,
                                            $statsTrackIdColumn => $statsModelWithSameName->id,
                                        ])->update([
                                            $statsTrackIdColumn => $statsModel->id,
                                        ]);

                                        StatsVisitor::where([
                                            'team_id' => $statsModelWithSameName->team_id,
                                            $statsTrackIdColumn => $statsModelWithSameName->id,
                                        ])->update([
                                            $statsTrackIdColumn => $statsModel->id,
                                        ]);

                                        $statsModelWithSameName->delete();
                                    }
                                });
                            });
                    });
                });

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });

        StatsAggregation::truncate();
    }
}
