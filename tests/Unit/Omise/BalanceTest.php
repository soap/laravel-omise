<?php

use Soap\LaravelOmise\Omise\Balance;
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

it('can create balance instance', function () {
    $config = new OmiseConfig();
    $balance = new Balance($config);
    
    expect($balance)->toBeInstanceOf(Balance::class);
});

it('can access balance properties', function () {
    $balance = new Balance(new OmiseConfig());
    
    $reflection = new ReflectionClass($balance);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'object' => 'balance',
        'livemode' => false,
        'total' => 350000,
        'currency' => 'thb',
        'transferable' => 100000,
        'reserve' => 50000,
    ];
    
    $objectProperty->setValue($balance, $mockData);
    
    expect($balance->total)->toBe(350000);
    expect($balance->currency)->toBe('thb');
    expect($balance->transferable)->toBe(100000);
    expect($balance->reserve)->toBe(50000);
    expect($balance->livemode)->toBeFalse();
});

it('can get total amount with conversion', function () {
    $balance = new Balance(new OmiseConfig());
    
    $reflection = new ReflectionClass($balance);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'object' => 'balance',
        'total' => 350000,
        'currency' => 'thb',
    ];
    
    $objectProperty->setValue($balance, $mockData);
    
    $amount = $balance->getTotalAmount();
    
    expect($amount)->toBeFloat();
    expect($amount)->toBe(3500.0);
});

it('can get transferable amount with conversion', function () {
    $balance = new Balance(new OmiseConfig());
    
    $reflection = new ReflectionClass($balance);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'object' => 'balance',
        'transferable' => 100000,
        'currency' => 'thb',
    ];
    
    $objectProperty->setValue($balance, $mockData);
    
    $amount = $balance->getTransferableAmount();
    
    expect($amount)->toBeFloat();
    expect($amount)->toBe(1000.0);
});

it('can get reserved amount with conversion', function () {
    $balance = new Balance(new OmiseConfig());
    
    $reflection = new ReflectionClass($balance);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'object' => 'balance',
        'reserve' => 50000,
        'currency' => 'thb',
    ];
    
    $objectProperty->setValue($balance, $mockData);
    
    $amount = $balance->getReservedAmount();
    
    expect($amount)->toBeFloat();
    expect($amount)->toBe(500.0);
});

it('can convert balance to array', function () {
    $balance = new Balance(new OmiseConfig());
    
    $reflection = new ReflectionClass($balance);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'object' => 'balance',
        'livemode' => false,
        'total' => 350000,
        'currency' => 'thb',
        'transferable' => 100000,
        'reserve' => 50000,
    ];
    
    $objectProperty->setValue($balance, $mockData);
    
    $array = $balance->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('object')
        ->toHaveKey('livemode')
        ->toHaveKey('total')
        ->toHaveKey('currency')
        ->toHaveKey('transferable')
        ->toHaveKey('reserve');
    
    expect($array['total'])->toBe(350000);
    expect($array['currency'])->toBe('thb');
});