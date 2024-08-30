<?php

namespace App\Console\Commands;

use App\Models\StatsAggregation;
use App\Models\StatsPageEntry;
use App\Models\StatsTrackReferer;
use App\Models\StatsUser;
use App\Models\StatsVisitor;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UnifyPageEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:unify-page-entries';

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
                StatsPageEntry::where('team_id', $team->id)->orderBy('id')->get()->each(function (StatsPageEntry $page) {
                    $pageName = $page->name;

                    $pageWithSlash = StatsPageEntry::where([
                        'team_id' => $page->team_id,
                        'project_id' => $page->project_id,
                        'name' => $pageName.'/',
                    ])->first();

                    if ($pageWithSlash) {
                        StatsUser::where([
                            'team_id' => $pageWithSlash->team_id,
                            'project_id' => $pageWithSlash->project_id,
                            'stats_page_entry_id' => $pageWithSlash->id,
                        ])->update([
                            'stats_page_entry_id' => $page->id,
                        ]);

                        StatsVisitor::where([
                            'team_id' => $pageWithSlash->team_id,
                            'project_id' => $pageWithSlash->project_id,
                            'stats_page_entry_id' => $pageWithSlash->id,
                        ])->update([
                            'stats_page_entry_id' => $page->id,
                        ]);

                        $pageWithSlash->delete();
                    }
                });
            } catch (\Exception $e) {
                DB::rollBack();
            }

            DB::commit();

            StatsAggregation::truncate();
        });
    }
}
