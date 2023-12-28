<?php

namespace App\Events;

use App\Models\Instance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Laravel\Jetstream\Jetstream;

class InstanceCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(Instance $instance)
    {
        $instance->createTokenPlain(
            $instance->name.Instance::TOKEN_NAME_POSTFIX,
        );
    }
}
