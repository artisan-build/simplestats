<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Non-sensitive hero A/B bucket label (see AbTestHeroVariant). Kept unencrypted
        // so the value read mid-request (set raw on the request bag) and the value
        // persisted in the cookie stay identical.
        'hero_variant',
    ];
}
