<?php

use Soap\LaravelOmise\Omise\Customer;
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

it('can create customer instance', function () {
    $config = new OmiseConfig;
    $customer = new Customer($config);

    expect($customer)->toBeInstanceOf(Customer::class);
});

it('can access customer properties', function () {
    $customer = new Customer(new OmiseConfig);

    $reflection = new ReflectionClass($customer);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);

    $mockData = [
        'id' => 'cust_test_123',
        'object' => 'customer',
        'livemode' => false,
        'email' => 'customer@example.com',
        'description' => 'Test Customer',
        'created_at' => '2025-01-01T00:00:00Z',
        'default_card' => 'card_test_123',
    ];

    $objectProperty->setValue($customer, $mockData);

    expect($customer->id)->toBe('cust_test_123');
    expect($customer->email)->toBe('customer@example.com');
    expect($customer->description)->toBe('Test Customer');
    expect($customer->default_card)->toBe('card_test_123');
    expect($customer->livemode)->toBeFalse();
});

it('can convert customer to array', function () {
    $customer = new Customer(new OmiseConfig);

    $reflection = new ReflectionClass($customer);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);

    $mockData = [
        'id' => 'cust_test_123',
        'object' => 'customer',
        'livemode' => false,
        'location' => '/customers/cust_test_123',
        'email' => 'customer@example.com',
        'description' => 'Test Customer',
        'created_at' => '2025-01-01T00:00:00Z',
        'default_card' => 'card_test_123',
        'metadata' => [
            'user_id' => '12345',
            'tier' => 'gold',
        ],
    ];

    $objectProperty->setValue($customer, $mockData);

    $array = $customer->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('id')
        ->toHaveKey('object')
        ->toHaveKey('email')
        ->toHaveKey('description')
        ->toHaveKey('default_card')
        ->toHaveKey('metadata');

    expect($array['id'])->toBe('cust_test_123');
    expect($array['email'])->toBe('customer@example.com');
    expect($array['metadata'])->toBeArray();
});
