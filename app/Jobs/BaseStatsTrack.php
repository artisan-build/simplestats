<?php

namespace App\Jobs;

use App\Models\Instance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class BaseStatsTrack // implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
//        protected int $instanceId,
        protected array $input,
    ) {
    }

    /**
     * If we want to put these jobs on the queue (enable "ShouldQueue" contract above),
     * we need to make sure that the Instance is set as the auth user (cause of global scope checks).
     * And we would also somehow need to add the requests to the queued job cause of HasInstanceScope and
     * somewhere there is also the request() helper used to get ip and user_agent.
     */
    public function handle(): void
    {
//        auth()->setUser(Instance::findOrFail($this->instanceId));
    }
}
