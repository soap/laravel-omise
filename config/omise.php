<?php

// config for Soap/LaravelOmise
return [
    'url' => 'https://api.omise.co',

    'live_public_key' => env('OMISE_LIVE_PUBLIC_KEY', ''),
    'live_secret_key' => env('OMISE_LIVE_SECRET_KEY', ''),

    'test_public_key' => env('OMISE_TEST_PUBLIC_KEY', ''),
    'test_secret_key' => env('OMISE_TEST_SECRET_KEY', ''),

    /*
     * Sent as the `Omise-Version` header of every request. Set it to null to use
     * the API version of your Omise account.
     */
    'api_version' => env('OMISE_API_VERSION', '2019-05-29'),

    'sandbox_status' => env('OMISE_SANDBOX_STATUS', true),

    /*
     * Failed API calls return a `Soap\LaravelOmise\Omise\Error` object. Set this to true
     * to throw `Soap\LaravelOmise\Exceptions\OmiseRequestException` instead, so a result
     * that is not checked cannot be mistaken for a loaded object.
     */
    'throw' => env('OMISE_THROW', false),

    /*
     * Payments created by payment method, see `Soap\LaravelOmise\Facades\Payment`.
     *
     * return_uri: where Omise sends the customer back after a redirect (3-D Secure,
     *             mobile banking), unless the payment gives its own.
     */
    'payments' => [
        'return_uri' => env('OMISE_RETURN_URI'),
    ],

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
