<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Soap\LaravelOmise\Facades\Omise;

beforeEach(function () {
    config([
        'omise.test_public_key' => 'pkey_test_fake',
        'omise.test_secret_key' => 'skey_test_fake',
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
        'omise.payments.return_uri' => null,
    ]);

    Http::preventStrayRequests();
});

function commandCharge(array $attributes = []): array
{
    return array_merge([
        'object' => 'charge',
        'id' => 'chrg_a',
        'livemode' => false,
        'amount' => 2050,
        'currency' => 'thb',
        'status' => 'successful',
        'paid' => true,
        'authorize_uri' => null,
        'failure_code' => null,
        'failure_message' => null,
        'expires_at' => null,
        'source' => null,
    ], $attributes);
}

it('creates a promptpay payment from the command', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(commandCharge([
        'status' => 'pending',
        'paid' => false,
        'expires_at' => '2026-10-04T10:00:00Z',
        'source' => ['type' => 'promptpay', 'scannable_code' => ['image' => ['download_uri' => 'https://example.com/qr.png']]],
    ]))]);

    $this->artisan('omise:pay promptpay 20.50 --description="Order 1"')
        ->expectsOutputToContain('Scan the QR code: https://example.com/qr.png')
        ->expectsOutputToContain('omise:payment-status chrg_a')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request['amount'] === '2050'
        && $request['source'] === ['type' => 'promptpay']
        && $request['description'] === 'Order 1');
});

it('charges the test card from the command', function () {
    Omise::fake([
        'vault.omise.co/tokens*' => Http::response(['object' => 'token', 'id' => 'tokn_test_a']),
        'api.omise.co/charges/' => Http::response(commandCharge()),
    ]);

    $this->artisan('omise:pay card 20.50 --test-card --authorize-only')
        ->expectsOutputToContain('The charge is paid.')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'vault.omise.co/tokens')
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('pkey_test_fake:'))
        && $request['card']['number'] === '4242424242424242');

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'api.omise.co/charges')
        && $request['card'] === 'tokn_test_a'
        && $request['capture'] === 'false');
});

it('does not tokenize the test card with live keys', function () {
    config(['omise.sandbox_status' => false, 'omise.live_public_key' => 'pkey_fake', 'omise.live_secret_key' => 'skey_fake']);

    Omise::fake();

    $this->artisan('omise:pay card 20 --test-card --force')
        ->expectsOutputToContain('--test-card is only available with test keys.')
        ->assertFailed();

    Http::assertNothingSent();
});

it('asks before charging with live keys', function () {
    config(['omise.sandbox_status' => false, 'omise.live_public_key' => 'pkey_fake', 'omise.live_secret_key' => 'skey_fake']);

    Omise::fake();

    $this->artisan('omise:pay promptpay 20')
        ->expectsConfirmation('The LIVE keys are in use, this creates a real charge. Continue?', 'no')
        ->assertFailed();

    Http::assertNothingSent();
});

it('reports a payment that cannot be created', function () {
    Omise::fake();

    $this->artisan('omise:pay mobile_banking 20 --bank=scb')
        ->expectsOutputToContain('A return_uri is required.')
        ->assertFailed();

    $this->artisan('omise:pay bitcoin 20')
        ->expectsOutputToContain('Payment method [bitcoin] is not supported.')
        ->assertFailed();

    $this->artisan('omise:pay promptpay 20 --currency=XXX')
        ->expectsOutputToContain('Unsupported currency [XXX].')
        ->assertFailed();

    Http::assertNothingSent();
});

it('reports a declined card', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(commandCharge([
        'status' => 'failed',
        'paid' => false,
        'failure_code' => 'insufficient_fund',
        'failure_message' => 'insufficient funds in the account',
    ]))]);

    $this->artisan('omise:pay card 20.50 --card=tokn_test_a')
        ->expectsOutputToContain('The charge failed: insufficient_fund - insufficient funds in the account')
        ->assertSuccessful();
});

it('tells how to pay with a card that requires 3-D Secure', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(commandCharge([
        'status' => 'failed',
        'paid' => false,
        'failure_code' => 'payment_rejected',
        'failure_message' => '3d secure is requested but return_uri is not set',
    ]))]);

    $this->artisan('omise:pay card 20.50 --card=tokn_test_a')
        ->expectsOutputToContain('Pass --return-uri=https://... or set OMISE_RETURN_URI')
        ->assertSuccessful();
});

it('shows the state of a payment', function () {
    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(commandCharge())]);

    $this->artisan('omise:payment-status chrg_a')
        ->expectsOutputToContain('The charge is paid.')
        ->assertSuccessful();

    $this->artisan('omise:payment-status chrg_a --json')
        ->expectsOutputToContain('"charge_id":"chrg_a"')
        ->assertSuccessful();
});
