<?php

// config for Soap/LaravelOmise
return [
    'url' => 'https://api.omise.co',

    'live_public_key' => env('OMISE_LIVE_PUBLIC_KEY', 'pkey_test_xxx'),
    'live_secret_key' => env('OMISE_LIVE_SECRET_KEY', 'skey_test_xxx'),

    'test_public_key' => env('OMISE_TEST_PUBLIC_KEY', ''),
    'test_secret_key' => env('OMISE_TEST_SECRET_KEY', ''),

    'api_version' => env('OMISE_API_VERSION', '2019-05-29'),

    'sanbox_status' => env('OMISE_SANDBOX_STATUS', true),
];
