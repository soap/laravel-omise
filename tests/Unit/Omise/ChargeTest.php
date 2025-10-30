<?php

use Soap\LaravelOmise\Omise\Charge;
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

it('can create charge instance', function () {
    $config = new OmiseConfig();
    $charge = new Charge($config);
    
    expect($charge)->toBeInstanceOf(Charge::class);
});

it('has validation methods', function () {
    $config = new OmiseConfig();
    $charge = new Charge($config);
    
    expect($charge->isLoaded())->toBeFalse();
    expect($charge->isValid())->toBeFalse();
});

it('can check property existence on objects and arrays', function () {
    $config = new OmiseConfig();
    $charge = new Charge($config);
    
    // Test with array
    $reflection = new ReflectionClass($charge);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    $objectProperty->setValue($charge, ['id' => 'chrg_test', 'status' => 'successful']);
    
    expect($charge->hasProperty('id'))->toBeTrue();
    expect($charge->hasProperty('status'))->toBeTrue();
    expect($charge->hasProperty('nonexistent'))->toBeFalse();
    
    // Test with object
    $obj = new stdClass();
    $obj->id = 'chrg_test';
    $obj->status = 'successful';
    $objectProperty->setValue($charge, $obj);
    
    expect($charge->hasProperty('id'))->toBeTrue();
    expect($charge->hasProperty('status'))->toBeTrue();
    expect($charge->hasProperty('nonexistent'))->toBeFalse();
});

it('can get property values from objects and arrays', function () {
    $config = new OmiseConfig();
    $charge = new Charge($config);
    
    $reflection = new ReflectionClass($charge);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    // Test with array
    $objectProperty->setValue($charge, ['id' => 'chrg_array', 'amount' => 100000]);
    expect($charge->getProperty('id'))->toBe('chrg_array');
    expect($charge->getProperty('amount'))->toBe(100000);
    expect($charge->getProperty('missing', 'default'))->toBe('default');
    
    // Test with object
    $obj = new stdClass();
    $obj->id = 'chrg_object';
    $obj->amount = 200000;
    $objectProperty->setValue($charge, $obj);
    
    expect($charge->getProperty('id'))->toBe('chrg_object');
    expect($charge->getProperty('amount'))->toBe(200000);
    expect($charge->getProperty('missing', 'default'))->toBe('default');
});

it('validates required properties correctly', function () {
    $config = new OmiseConfig();
    $charge = new Charge($config);
    
    $reflection = new ReflectionClass($charge);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    // Complete object
    $objectProperty->setValue($charge, [
        'id' => 'chrg_test',
        'status' => 'successful',
        'paid' => true,
        'amount' => 100000,
        'currency' => 'thb'
    ]);
    
    expect($charge->isValid())->toBeTrue();
    
    // Incomplete object
    $objectProperty->setValue($charge, [
        'id' => 'chrg_test',
        'status' => 'successful'
    ]);
    
    expect($charge->isValid())->toBeFalse();
});