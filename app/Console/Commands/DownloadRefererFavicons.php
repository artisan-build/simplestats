<?php

namespace App\Console\Commands;

use App\Models\StatsTrackReferer;
use App\Services\FaviconService;
use Illuminate\Console\Command;

class DownloadRefererFavicons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-referer-favicons ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(FaviconService $faviconService): void
    {
        StatsTrackReferer::select(['id', 'name'])->distinct()->orderBy('id')->get()->each(function (StatsTrackReferer $refererStat) use ($faviconService) {
            $faviconService->download($refererStat->name);
        });
    }
}
