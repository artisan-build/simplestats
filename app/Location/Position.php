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

    /**
     * The autonomous system number the IP is announced from (e.g. 16509 for
     * AWS). Used to keep consumer-VPN exits, which sit on datacenter IPs but
     * carry real people. Null when the resolving driver does not provide it.
     */
    public ?int $asn = null;
}
