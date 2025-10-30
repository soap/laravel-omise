<?php

use Soap\LaravelOmise\Omise\Source;
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

it('can create source instance', function () {
    $config = new OmiseConfig();
    $source = new Source($config);
    
    expect($source)->toBeInstanceOf(Source::class);
});

it('can access source properties', function () {
    $source = new Source(new OmiseConfig());
    
    $reflection = new ReflectionClass($source);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'id' => 'src_test_123',
        'object' => 'source',
        'type' => 'promptpay',
        'flow' => 'redirect',
        'amount' => 100000,
        'currency' => 'thb',
    ];
    
    $objectProperty->setValue($source, $mockData);
    
    expect($source->id)->toBe('src_test_123');
    expect($source->type)->toBe('promptpay');
    expect($source->flow)->toBe('redirect');
    expect($source->amount)->toBe(100000);
    expect($source->currency)->toBe('thb');
});

it('can convert source to array', function () {
    $source = new Source(new OmiseConfig());
    
    $reflection = new ReflectionClass($source);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'id' => 'src_test_123',
        'object' => 'source',
        'livemode' => false,
        'location' => '/sources/src_test_123',
        'type' => 'promptpay',
        'flow' => 'redirect',
        'amount' => 100000,
        'currency' => 'thb',
        'created_at' => '2025-01-01T00:00:00Z',
    ];
    
    $objectProperty->setValue($source, $mockData);
    
    $array = $source->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('id')
        ->toHaveKey('object')
        ->toHaveKey('type')
        ->toHaveKey('flow')
        ->toHaveKey('amount')
        ->toHaveKey('currency');
    
    expect($array['id'])->toBe('src_test_123');
    expect($array['type'])->toBe('promptpay');
    expect($array['amount'])->toBe(100000);
});