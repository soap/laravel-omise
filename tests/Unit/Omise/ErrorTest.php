<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Soap\LaravelOmise\Facades\Omise;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\Omise\Helpers\OmiseMoney;

beforeEach(function () {
    config([
        'omise.test_public_key' => 'pkey_test_fake',
        'omise.test_secret_key' => 'skey_test_fake',
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
    ]);

    Http::preventStrayRequests();
});

it('keeps the error code Omise answered with', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(['object' => 'error', 'code' => 'invalid_card', 'message' => 'the card is invalid'], 400)]);

    $result = Omise::charge()->create(['amount' => 10000]);

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->getCode())->toBe('bad_request')
        ->and($result->getOmiseCode())->toBe('invalid_card')
        ->and($result->toArray())->toBe([
            'object' => 'error',
            'code' => 'bad_request',
            'omise_code' => 'invalid_card',
            'message' => 'the card is invalid',
        ]);
});

it('has no Omise code when the request did not reach Omise', function () {
    Omise::fake(['api.omise.co/charges/' => fn () => throw new ConnectionException('Connection timed out')]);

    $result = Omise::charge()->create(['amount' => 10000]);

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->getOmiseCode())->toBeNull()
        ->and($result->getException())->toBeInstanceOf(ConnectionException::class);
});

it('throws failed api calls when configured to', function () {
    config(['omise.throw' => true]);

    Omise::fake(['api.omise.co/charges/*' => Http::response(['object' => 'error', 'code' => 'not_found', 'message' => 'charge chrg_a was not found'], 404)]);

    try {
        Omise::charge()->find('chrg_a');
    } catch (OmiseRequestException $exception) {
        expect($exception->getMessage())->toBe('charge chrg_a was not found')
            ->and($exception->getErrorCode())->toBe('api_error')
            ->and($exception->getOmiseCode())->toBe('not_found')
            ->and($exception->getPrevious())->toBeInstanceOf(OmiseNotFoundException::class);

        return;
    }

    $this->fail('The error was not thrown.');
});

it('still returns the object of a successful call when configured to throw', function () {
    config(['omise.throw' => true]);

    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(['object' => 'charge', 'id' => 'chrg_a'])]);

    expect(Omise::charge()->find('chrg_a'))->toBeInstanceOf(Charge::class);
});

it('reports what is missing from the configuration', function () {
    expect(Omise::configErrors())->toBe([]);

    config(['omise.test_secret_key' => '']);

    expect(Omise::configErrors())->toBe(['Secret key is missing']);
});

it('has no placeholder for the live keys', function () {
    $keys = ['OMISE_LIVE_PUBLIC_KEY', 'OMISE_LIVE_SECRET_KEY'];
    $previous = array_map('getenv', $keys);

    array_map('putenv', $keys);

    try {
        $config = require __DIR__.'/../../../config/omise.php';
    } finally {
        foreach ($keys as $index => $key) {
            if ($previous[$index] !== false) {
                putenv("{$key}={$previous[$index]}");
            }
        }
    }

    expect($config['live_public_key'])->toBe('')
        ->and($config['live_secret_key'])->toBe('');
});

it('explains why an amount cannot be converted', function () {
    expect(fn () => OmiseMoney::toSubunit(100, 'xxx'))->toThrow(InvalidArgumentException::class, 'Unsupported currency [XXX].')
        ->and(fn () => OmiseMoney::toCurrencyUnit('abc', 'thb'))->toThrow(InvalidArgumentException::class, 'The amount must be numeric.');
});

it('names the object in the array of a resource', function (string $resource, string $object) {
    $instance = Omise::{$resource}();

    (new ReflectionProperty($instance, 'object'))->setValue($instance, ['object' => $object, 'id' => 'test_id', 'currency' => 'thb']);

    expect($instance->toArray()['object'])->toBe($object);
})->with([
    ['customer', 'customer'],
    ['balance', 'balance'],
    ['source', 'source'],
]);
