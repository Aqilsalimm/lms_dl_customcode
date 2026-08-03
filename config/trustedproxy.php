<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The IP addresses / CIDR ranges of reverse proxies that sit in front of
    | this application. Laravel only honours X-Forwarded-* headers when the
    | request actually arrives from one of these addresses.
    |
    | Leave TRUSTED_PROXIES empty (the default) when the app is reached
    | directly. Setting it to '*' trusts the forwarded headers of every caller,
    | which lets anyone spoof their client IP and defeat all IP-based rate
    | limiting — do not do that.
    |
    | Examples:
    |   TRUSTED_PROXIES=10.0.0.0/8
    |   TRUSTED_PROXIES=172.18.0.1,10.0.0.0/8
    |   TRUSTED_PROXIES=REMOTE_ADDR      (trust only the immediate upstream)
    |
    | Cloudflare users: list Cloudflare's published IP ranges here.
    |
    */

    'proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ), fn ($ip) => $ip !== '')) ?: null,

];
