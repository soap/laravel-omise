<?php

use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\OmiseConfig;

beforeEach(function () {
    putenv('OMISE_TEST_PUBLIC_KEY=pkey_test_fake');
    putenv('OMISE_TEST_SECRET_KEY=skey_test_fake');
    putenv('OMISE_SANDBOX_STATUS=true');

    config([
        'omise.test_public_key' => getenv('OMISE_TEST_PUBLIC_KEY'),
        'omise.test_secret_key' => getenv('OMISE_TEST_SECRET_KEY'),
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
    ]);
});

it('can create charge instance', function () {
    $config = new OmiseConfig;
    $charge = new Charge($config);

    expect($charge)->toBeInstanceOf(Charge::class);
});

it('has validation methods', function () {
    $config = new OmiseConfig;
    $charge = new Charge($config);

    expect($charge->isLoaded())->toBeFalse();
    expect($charge->isValid())->toBeFalse();
});

it('can check property existence on objects and arrays', function () {
    $config = new OmiseConfig;
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
    $obj = new stdClass;
    $obj->id = 'chrg_test';
    $obj->status = 'successful';
    $objectProperty->setValue($charge, $obj);

    expect($charge->hasProperty('id'))->toBeTrue();
    expect($charge->hasProperty('status'))->toBeTrue();
    expect($charge->hasProperty('nonexistent'))->toBeFalse();
});

it('can get property values from objects and arrays', function () {
    $config = new OmiseConfig;
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
    $obj = new stdClass;
    $obj->id = 'chrg_object';
    $obj->amount = 200000;
    $objectProperty->setValue($charge, $obj);

    expect($charge->getProperty('id'))->toBe('chrg_object');
    expect($charge->getProperty('amount'))->toBe(200000);
    expect($charge->getProperty('missing', 'default'))->toBe('default');
});

it('validates required properties correctly', function () {
    $config = new OmiseConfig;
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
        'currency' => 'thb',
    ]);

    expect($charge->isValid())->toBeTrue();

    // Incomplete object
    $objectProperty->setValue($charge, [
        'id' => 'chrg_test',
        'status' => 'successful',
    ]);

    expect($charge->isValid())->toBeFalse();
});

function chargeWith(array $attributes): Charge
{
    $charge = new Charge(new OmiseConfig);

    (new ReflectionProperty($charge, 'object'))->setValue($charge, $attributes);

    return $charge;
}

it('converts a charge to an array', function () {
    $attributes = ['object' => 'charge', 'id' => 'chrg_test', 'amount' => 100000, 'currency' => 'thb'];

    expect(chargeWith($attributes)->toArray())->toBe($attributes)
        ->and((new Charge(new OmiseConfig))->toArray())->toBe([]);
});

it('reads attributes as methods', function () {
    $charge = chargeWith(['id' => 'chrg_test', 'authorize_uri' => null, 'failure_code' => 'insufficient_fund']);

    expect($charge->authorizeUri())->toBeNull()
        ->and($charge->failureCode())->toBe('insufficient_fund');
});

it('rejects a method that is not an attribute of the loaded charge', function () {
    chargeWith(['id' => 'chrg_test'])->isSucessful();
})->throws(BadMethodCallException::class, 'isSucessful');

it('knows a charge is fully refunded whatever its amount', function (array $attributes, bool $fullyRefunded, $refundedAmount) {
    $charge = chargeWith($attributes);

    expect($charge->isFullyRefunded())->toBe($fullyRefunded)
        ->and($charge->getRefundedAmount())->toEqual($refundedAmount);
})->with([
    'amount with satang' => [['amount' => 12345, 'currency' => 'thb', 'refunds' => ['data' => [['amount' => 12345]]]], true, 123.45],
    'several refunds' => [['amount' => 12345, 'currency' => 'thb', 'refunds' => ['data' => [['amount' => 12000], ['amount' => 345]]]], true, 123.45],
    'partial refund' => [['amount' => 12345, 'currency' => 'thb', 'refunds' => ['data' => [['amount' => 345]]]], false, 3.45],
    'no refund' => [['amount' => 12345, 'currency' => 'thb', 'refunds' => ['data' => []]], false, 0],
    'currency without subunit' => [['amount' => 500, 'currency' => 'jpy', 'refunds' => ['data' => [['amount' => 500]]]], true, 500],
    'refunded_amount of the api' => [['amount' => 12345, 'currency' => 'thb', 'refunded_amount' => 12345, 'refunds' => ['data' => []]], true, 123.45],
    'not loaded' => [[], false, 0],
]);
