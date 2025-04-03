<?php

beforeEach(function () {
    putenv('OMISE_TEST_PUBLIC_KEY=omise_test_public_key');
    putenv('OMISE_TEST_SECRET_KEY=omise_test_secret_key');

    putenv('OMISE_LIVE_PUBLIC_KEY=omise_live_public_key');
    putenv('OMISE_LIVE_SECRET_KEY=omise_live_secret_key');

    putenv('OMISE_SANDBOX_STATUS=true');
    putenv('OMISE_URL=https://api.omise.co');

    config([
        'omise.test_public_key' => getenv('OMISE_TEST_PUBLIC_KEY'),
        'omise.test_secret_key' => getenv('OMISE_TEST_SECRET_KEY'),
        'omise.live_public_key' => getenv('OMISE_LIVE_PUBLIC_KEY'),
        'omise.live_secret_key' => getenv('OMISE_LIVE_SECRET_KEY'),
        'omise.sandbox_status' => getenv('OMISE_SANDBOX_STATUS'),
        'omise.url' => getenv('OMISE_URL', true),
    ]);
});

it('gets test keys if sandbox is enabled', function () {
    $omise = app('omise');

    expect($omise->liveMode())->toBeFalse();
    expect($omise->getPublicKey())->toBe(getenv('OMISE_TEST_PUBLIC_KEY'));
    expect($omise->getSecretKey())->toBe(getenv('OMISE_TEST_SECRET_KEY'));
});

it('gets live keys if sandbox is disabled', function () {
    config(['omise.sandbox_status' => false]);

    $omise = app('omise');

    expect($omise->liveMode())->toBeTrue();
    expect($omise->getPublicKey())->toBe(getenv('OMISE_LIVE_PUBLIC_KEY'));
    expect($omise->getSecretKey())->toBe(getenv('OMISE_LIVE_SECRET_KEY'));
});
