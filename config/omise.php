<?php

// config for Soap/LaravelOmise
return [
    'url' => 'https://api.omise.co',

    'live_public_key' => env('OMISE_LIVE_PUBLIC_KEY', 'pkey_test_xxx'),
    'live_secret_key' => env('OMISE_LIVE_SECRET_KEY', 'skey_test_xxx'),

    'test_public_key' => env('OMISE_TEST_PUBLIC_KEY', ''),
    'test_secret_key' => env('OMISE_TEST_SECRET_KEY', ''),

    /*
     * Sent as the `Omise-Version` header of every request. Set it to null to use
     * the API version of your Omise account.
     */
    'api_version' => env('OMISE_API_VERSION', '2019-05-29'),

    'sandbox_status' => env('OMISE_SANDBOX_STATUS', true),

    /*
     * How requests are sent to the Omise API.
     *
     * driver: "sdk" uses the curl client of omise/omise-php (30s connect / 60s timeout).
     *         "laravel" uses the Laravel HTTP client: it honours the `url` above and
     *         the timeouts below, and can be faked with `Http::fake()` / `Omise::fake()`.
     */
    'http' => [
        'driver' => env('OMISE_HTTP_DRIVER', 'sdk'),
        'timeout' => env('OMISE_HTTP_TIMEOUT', 60),
        'connect_timeout' => env('OMISE_HTTP_CONNECT_TIMEOUT', 30),
    ],
];
