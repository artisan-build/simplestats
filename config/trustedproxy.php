<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by the framework's TrustProxies middleware when the middleware
    | itself defines no proxies. Use "*" to trust the calling proxy (e.g. in
    | the Docker setup behind Caddy or Traefik) or a comma separated list of
    | IPs / CIDR ranges. Without it, HTTPS and the client IP are not detected
    | behind a reverse proxy.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
