<?php

namespace App\Location;

use Stevebauman\Location\Position as BasePosition;

class Position extends BasePosition
{
    /**
     * Whether the IP is a proxy, VPN, or Tor exit node. Null when the
     * resolving driver does not provide the flag (e.g. fallback drivers).
     */
    public ?bool $isProxy = null;

    /**
     * Whether the IP belongs to a hosting provider / datacenter. Null when
     * the resolving driver does not provide the flag.
     */
    public ?bool $isHosting = null;
}
