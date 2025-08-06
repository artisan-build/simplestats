<?php

namespace App\Jobs;

use App\Models\Instance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;

abstract class BaseStatsTrack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $input,
    ) {
        $this->onQueue('ingest');
    }

    public function tags(): array
    {
        $tags = [
            'StatsTrack',
        ];

        if ($instanceId = Context::get('instance_id')) {
            $tags[] = 'Instance:'.$instanceId;
        }

        if (isset($this->input['id'])) {
            $tags[] = 'ID:'.$this->input['id'];
        }

        if (isset($this->input['ip'])) {
            $tags[] = 'IP:'.$this->input['ip'];
        }

        return $tags;
    }

    public function handle(): void
    {
        auth()->setUser(Instance::withoutGlobalScope('team')->findOrFail(Context::get('instance_id')));
    }
}
