<?php

use Soap\LaravelOmise\Omise\Account;
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

it('can create account instance', function () {
    $config = new OmiseConfig;
    $account = new Account($config);

    expect($account)->toBeInstanceOf(Account::class);
});

it('can access account properties', function () {
    $account = new Account(new OmiseConfig);

    $reflection = new ReflectionClass($account);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);

    $mockData = [
        'id' => 'acct_test_123',
        'email' => 'test@example.com',
        'team' => 'Test Team',
        'country' => 'TH',
        'currency' => 'thb',
        'api_version' => '2019-05-29',
        'livemode' => false,
        'webhook_uri' => 'https://example.com/webhook',
        'supported_currencies' => ['THB', 'USD'],
    ];

    $objectProperty->setValue($account, $mockData);

    expect($account->id)->toBe('acct_test_123');
    expect($account->email)->toBe('test@example.com');
    expect($account->country)->toBe('TH');
    expect($account->currency)->toBe('thb');
    expect($account->livemode)->toBeFalse();
});

it('can convert account to array', function () {
    $account = new Account(new OmiseConfig);

    $reflection = new ReflectionClass($account);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);

    $mockData = [
        'id' => 'acct_test_123',
        'team' => 'Test Team',
        'email' => 'test@example.com',
        'livemode' => false,
        'location' => '/account',
        'webhook_uri' => 'https://example.com/webhook',
        'country' => 'TH',
        'api_version' => '2019-05-29',
        'currency' => 'thb',
        'supported_currencies' => ['THB', 'USD'],
        'auto_activate_recipients' => true,
        'zero_interest_installments' => false,
        'chain_enabled' => false,
        'chaining_allowed' => false,
        'created_at' => '2025-01-01T00:00:00Z',
    ];

    $objectProperty->setValue($account, $mockData);

    $array = $account->toArray();

    expect($array)->toBeArray()
        ->toHaveKey('id')
        ->toHaveKey('email')
        ->toHaveKey('country')
        ->toHaveKey('currency')
        ->toHaveKey('supported_currencies');

    expect($array['id'])->toBe('acct_test_123');
    expect($array['country'])->toBe('TH');
});
