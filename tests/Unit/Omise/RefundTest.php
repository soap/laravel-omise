<?php

use Soap\LaravelOmise\Omise\Refund;
use Soap\LaravelOmise\OmiseConfig;

beforeEach(function () {
    putenv('OMISE_TEST_PUBLIC_KEY=pkey_test_5q2qjs6ks3kehbic85t');
    putenv('OMISE_TEST_SECRET_KEY=skey_test_5q2qjs6kst7j985ncow');
    putenv('OMISE_SANDBOX_STATUS=true');

    config([
        'omise.test_public_key' => getenv('OMISE_TEST_PUBLIC_KEY'),
        'omise.test_secret_key' => getenv('OMISE_TEST_SECRET_KEY'),
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
    ]);
});

it('can create refund instance', function () {
    $config = new OmiseConfig;
    $refund = new Refund($config);

    expect($refund)->toBeInstanceOf(Refund::class);
});

it('can access refund properties', function () {
    $refund = new Refund(new OmiseConfig);

    $reflection = new ReflectionClass($refund);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);

    $mockData = [
        'id' => 'rfnd_test_123',
        'object' => 'refund',
        'livemode' => false,
        'amount' => 50000,
        'currency' => 'thb',
        'charge' => 'chrg_test_123',
        'transaction' => 'trxn_test_123',
        'status' => 'succeeded',
    ];

    $objectProperty->setValue($refund, $mockData);

    expect($refund->id)->toBe('rfnd_test_123');
    expect($refund->amount)->toBe(50000);
    expect($refund->currency)->toBe('thb');
    expect($refund->charge)->toBe('chrg_test_123');
    expect($refund->status)->toBe('succeeded');
});

it('can convert refund to array', function () {
    $refund = new Refund(new OmiseConfig);

    $reflection = new ReflectionClass($refund);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);

    $mockData = [
        'id' => 'rfnd_test_123',
        'object' => 'refund',
        'livemode' => false,
        'location' => '/charges/chrg_test_123/refunds/rfnd_test_123',
        'amount' => 50000,
        'currency' => 'thb',
        'charge' => 'chrg_test_123',
        'transaction' => 'trxn_test_123',
        'status' => 'succeeded',
        'created_at' => '2025-01-01T00:00:00Z',
        'metadata' => [
            'reason' => 'customer_request',
        ],
    ];

    $objectProperty->setValue($refund, $mockData);

    $array = $refund->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('id')
        ->toHaveKey('object')
        ->toHaveKey('amount')
        ->toHaveKey('currency')
        ->toHaveKey('charge')
        ->toHaveKey('status');

    expect($array['id'])->toBe('rfnd_test_123');
    expect($array['amount'])->toBe(50000);
    expect($array['status'])->toBe('succeeded');
});
