<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Soap\LaravelOmise\Events\ChargeCreated;
use Soap\LaravelOmise\Events\RefundCreated;
use Soap\LaravelOmise\Events\RequestFailed;
use Soap\LaravelOmise\Facades\Omise;
use Soap\LaravelOmise\Omise as OmiseManager;

beforeEach(function () {
    config([
        'omise.test_public_key' => 'pkey_test_fake',
        'omise.test_secret_key' => 'skey_test_fake',
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
    ]);

    Http::preventStrayRequests();
});

function omiseCharge(array $attributes = []): array
{
    return array_merge(['object' => 'charge', 'id' => 'chrg_a', 'amount' => 10000, 'currency' => 'thb'], $attributes);
}

it('resolves the same instance from the class, the alias and the facade', function () {
    expect(app(OmiseManager::class))->toBe(app('omise'))
        ->and(Omise::getFacadeRoot())->toBe(app(OmiseManager::class));
});

it('follows the configuration after it is resolved', function () {
    $omise = app('omise');

    config(['omise.test_secret_key' => '']);

    expect($omise->validConfig())->toBeFalse();
});

it('sends requests with the keys of another account', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(omiseCharge())]);

    $tenant = Omise::withKeys('pkey_tenant', 'skey_tenant');

    $tenant->charge()->create(['amount' => 10000]);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('skey_tenant:')));

    expect($tenant->getPublicKey())->toBe('pkey_tenant')
        ->and($tenant->getSecretKey())->toBe('skey_tenant')
        ->and($tenant->validConfig())->toBeTrue()
        ->and($tenant->liveMode())->toBeTrue()
        ->and(Omise::withKeys('pkey_test_tenant', 'skey_test_tenant')->liveMode())->toBeFalse();
});

it('leaves the configured keys untouched when other keys are used', function () {
    Omise::withKeys('pkey_tenant', 'skey_tenant');

    expect(Omise::getSecretKey())->toBe('skey_test_fake')
        ->and(Omise::liveMode())->toBeFalse();
});

it('dispatches an event when a charge is created', function () {
    Event::fake();
    Omise::fake(['api.omise.co/charges/' => Http::response(omiseCharge())]);

    $charge = Omise::charge()->create(['amount' => 10000]);

    Event::assertDispatched(fn (ChargeCreated $event) => $event->charge === $charge);
    Event::assertNotDispatched(RequestFailed::class);
});

it('dispatches an event when a charge is refunded', function () {
    Event::fake();
    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(omiseCharge()),
        'api.omise.co/charges/chrg_a/refunds' => Http::response(['object' => 'refund', 'id' => 'rfnd_a', 'amount' => 5000]),
    ]);

    $charge = Omise::charge()->find('chrg_a');
    $charge->refund(['amount' => 5000]);

    Event::assertDispatched(fn (RefundCreated $event) => $event->charge === $charge && $event->refund['id'] === 'rfnd_a');
    Event::assertNotDispatched(ChargeCreated::class);
});

it('dispatches an event when an api call failed', function () {
    Event::fake();
    Omise::fake(['api.omise.co/charges/' => Http::response(['object' => 'error', 'code' => 'invalid_card', 'message' => 'the card is invalid'], 400)]);

    $error = Omise::charge()->create(['amount' => 10000]);

    Event::assertDispatched(fn (RequestFailed $event) => $event->error === $error && $event->error->getOmiseCode() === 'invalid_card');
    Event::assertNotDispatched(ChargeCreated::class);
});

it('serializes a resource to json', function () {
    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(omiseCharge())]);

    $charge = Omise::charge()->find('chrg_a');

    expect(json_decode(json_encode($charge), true))->toBe(omiseCharge())
        ->and(collect(['charge' => $charge])->toArray())->toBe(['charge' => omiseCharge()]);
});

it('knows which attributes are set', function () {
    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(omiseCharge(['authorize_uri' => null]))]);

    $charge = Omise::charge()->find('chrg_a');

    expect(isset($charge->id))->toBeTrue()
        ->and(isset($charge->authorize_uri))->toBeFalse()
        ->and(isset($charge->missing))->toBeFalse()
        ->and(isset(Omise::charge()->id))->toBeFalse();
});

it('reports the error from a command when failed calls are thrown', function () {
    config(['omise.throw' => true]);

    Omise::fake(['api.omise.co/account*' => Http::response(['object' => 'error', 'code' => 'authentication_failure', 'message' => 'authentication failed'], 401)]);

    $this->artisan('omise:verify')
        ->expectsOutputToContain('authentication failed')
        ->assertFailed();
});
