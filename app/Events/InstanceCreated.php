<?php

namespace App\Events;

use App\Models\Instance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InstanceCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Instance $instance)
    {
        $instance->createTokenPlain(Instance::TOKEN_NAME);
    }
}
