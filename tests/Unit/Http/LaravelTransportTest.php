<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Soap\LaravelOmise\Facades\Omise;
use Soap\LaravelOmise\Http\Transport;
use Soap\LaravelOmise\Omise\Account;
use Soap\LaravelOmise\Omise\Balance;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Customer;
use Soap\LaravelOmise\Omise\Error;

beforeEach(function () {
    config([
        'omise.test_public_key' => 'pkey_test_fake',
        'omise.test_secret_key' => 'skey_test_fake',
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
        'omise.api_version' => '2019-05-29',
    ]);

    Http::preventStrayRequests();
});

function fakeCharge(array $attributes = []): array
{
    return array_merge([
        'object' => 'charge',
        'id' => 'chrg_a',
        'amount' => 10000,
        'currency' => 'thb',
        'status' => 'pending',
        'paid' => false,
    ], $attributes);
}

function fakeCustomer(array $attributes = []): array
{
    return array_merge([
        'object' => 'customer',
        'id' => 'cust_a',
        'email' => 'old@example.com',
        'cards' => ['object' => 'list', 'data' => [['object' => 'card', 'id' => 'card_a']]],
    ], $attributes);
}

it('sends requests through the Laravel HTTP client', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(fakeCharge())]);

    $charge = Omise::charge()->create(['amount' => 10000, 'currency' => 'thb', 'card' => 'tokn_test']);

    expect($charge)->toBeInstanceOf(Charge::class)
        ->and($charge->id)->toBe('chrg_a')
        ->and($charge->isError())->toBeFalse();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.omise.co/charges/'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('skey_test_fake:'))
        && $request->hasHeader('Omise-Version', '2019-05-29')
        && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
        && $request['amount'] === '10000'
        && $request['card'] === 'tokn_test');
});

it('encodes lists the same way as the SDK', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(fakeCharge())]);

    Omise::charge()->create(['amount' => 10000, 'platform_fee' => ['fixed' => 100], 'tags' => ['a', 'b']]);

    Http::assertSent(fn (Request $request) => str_contains($request->body(), 'tags%5B%5D=a&tags%5B%5D=b')
        && str_contains($request->body(), 'platform_fee%5Bfixed%5D=100'));
});

it('omits the version header when no api version is configured', function () {
    config(['omise.api_version' => null]);

    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(fakeCharge())]);

    Omise::charge()->find('chrg_a');

    Http::assertSent(fn (Request $request) => ! $request->hasHeader('Omise-Version'));
});

it('honours the configured url', function () {
    config(['omise.url' => 'https://omise.test']);

    Omise::fake(['omise.test/*' => Http::response(fakeCharge())]);

    expect(Omise::charge()->find('chrg_a')->id)->toBe('chrg_a');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://omise.test/charges/chrg_a');
});

it('keeps the executor when another resource is used for the first time', function () {
    Omise::fake([
        'api.omise.co/balance*' => Http::response(['object' => 'balance', 'total' => 500, 'currency' => 'thb']),
        'api.omise.co/account*' => Http::response(['object' => 'account', 'id' => 'acct_a']),
    ]);

    expect(Omise::balance()->retrieve())->toBeInstanceOf(Balance::class)
        ->and(Omise::account()->retrieve())->toBeInstanceOf(Account::class);

    Http::assertSentCount(2);
});

it('keeps each loaded charge separate', function () {
    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(fakeCharge()),
        'api.omise.co/charges/chrg_b' => Http::response(fakeCharge(['id' => 'chrg_b', 'amount' => 20000])),
    ]);

    $first = Omise::charge()->find('chrg_a');
    $second = Omise::charge()->find('chrg_b');

    expect($first->id)->toBe('chrg_a')
        ->and($first->amount)->toBe(10000)
        ->and($second->id)->toBe('chrg_b');
});

it('captures, reverses and expires a charge without the SDK key constants', function () {
    expect(defined('OMISE_SECRET_KEY'))->toBeFalse();

    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(fakeCharge()),
        'api.omise.co/charges/chrg_a/capture' => Http::response(fakeCharge(['status' => 'successful', 'paid' => true])),
        'api.omise.co/charges/chrg_a/reverse' => Http::response(fakeCharge(['status' => 'reversed'])),
        'api.omise.co/charges/chrg_b/expire' => Http::response(fakeCharge(['id' => 'chrg_b', 'status' => 'expired'])),
    ]);

    $charge = Omise::charge()->find('chrg_a');

    expect($charge->capture()->isSuccessful())->toBeTrue()
        ->and($charge->reverse()->status)->toBe('reversed');

    $expired = Omise::charge()->expire('chrg_b');

    expect($expired)->toBeInstanceOf(Charge::class)
        ->and($expired->id)->toBe('chrg_b')
        ->and($expired->status)->toBe('expired');

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://api.omise.co/charges/chrg_b/expire'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('skey_test_fake:')));
});

it('refunds a charge', function () {
    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(fakeCharge()),
        'api.omise.co/charges/chrg_a/refunds' => Http::response(['object' => 'refund', 'id' => 'rfnd_a', 'amount' => 5000]),
    ]);

    $refund = Omise::charge()->find('chrg_a')->refund(['amount' => 5000]);

    expect($refund)->toBeInstanceOf(OmiseRefund::class)
        ->and($refund['id'])->toBe('rfnd_a');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/refunds') && $request['amount'] === '5000');
});

it('returns an error for an action on a charge that is not loaded', function () {
    Omise::fake();

    $result = Omise::charge()->capture();

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->getCode())->toBe('failed_capture');

    Http::assertNothingSent();
});

it('updates a loaded customer', function () {
    Omise::fake([
        'api.omise.co/customers/cust_a' => Http::sequence()
            ->push(fakeCustomer())
            ->push(fakeCustomer(['email' => 'new@example.com'])),
    ]);

    $customer = Omise::customer()->find('cust_a');
    $result = $customer->update(['email' => 'new@example.com']);

    expect($result)->toBe($customer)
        ->and($customer->email)->toBe('new@example.com');

    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && $request['email'] === 'new@example.com');
});

it('updates a customer by id without loading it', function () {
    Omise::fake(['api.omise.co/customers/cust_a' => Http::response(fakeCustomer(['default_card' => 'card_b']))]);

    $customer = Omise::customer()->update(['card' => 'tokn_test'], 'cust_a');

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->default_card)->toBe('card_b');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && $request['card'] === 'tokn_test');
});

it('deletes a card of a customer', function () {
    Omise::fake(['api.omise.co/customers/cust_a/cards/card_a' => Http::response(['object' => 'card', 'id' => 'card_a', 'deleted' => true])]);

    expect(Omise::customer()->deleteCard('card_a', 'cust_a'))->toBeInstanceOf(Customer::class);

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === 'https://api.omise.co/customers/cust_a/cards/card_a');
});

it('lists the cards of a customer', function () {
    Omise::fake([
        'api.omise.co/customers/cust_a' => Http::response(fakeCustomer()),
        'api.omise.co/customers/cust_a/cards?limit=1' => Http::response(['object' => 'list', 'data' => [['object' => 'card', 'id' => 'card_b']]]),
    ]);

    $customer = Omise::customer()->find('cust_a');

    expect($customer->cards())->toBeInstanceOf(OmiseCardList::class)
        ->and($customer->cards()['data'][0]['id'])->toBe('card_a')
        ->and($customer->cards(['limit' => 1])['data'][0]['id'])->toBe('card_b');
});

it('updates the webhook uri of the account', function () {
    Omise::fake(['api.omise.co/account' => Http::response(['object' => 'account', 'id' => 'acct_a', 'webhook_uri' => 'https://example.com/omise'])]);

    $account = Omise::account()->updateWebhookUri('https://example.com/omise');

    expect($account)->toBeInstanceOf(Account::class)
        ->and($account->webhook_uri)->toBe('https://example.com/omise');

    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && $request['webhook_uri'] === 'https://example.com/omise');
});

it('returns an error that can be thrown', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(['object' => 'error', 'code' => 'invalid_card', 'message' => 'the card is invalid'], 400)]);

    $result = Omise::charge()->create(['amount' => 10000]);

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->isError())->toBeTrue()
        ->and($result->getCode())->toBe('bad_request')
        ->and($result->getMessage())->toBe('the card is invalid')
        ->and($result->getException())->toBeInstanceOf(OmiseInvalidCardException::class);

    try {
        $result->throw();
    } catch (OmiseRequestException $exception) {
        expect($exception->getMessage())->toBe('the card is invalid')
            ->and($exception->getErrorCode())->toBe('bad_request')
            ->and($exception->getError())->toBe($result)
            ->and($exception->getPrevious())->toBeInstanceOf(OmiseInvalidCardException::class);

        return;
    }

    $this->fail('The error was not thrown.');
});

it('returns the object when there is nothing to throw', function () {
    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(fakeCharge())]);

    $charge = Omise::charge()->find('chrg_a');

    expect($charge->throw())->toBe($charge);
});

it('refunds a charge from the command', function () {
    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(fakeCharge()),
        'api.omise.co/charges/chrg_a/refunds' => Http::response(['object' => 'refund', 'id' => 'rfnd_a', 'amount' => 5000]),
    ]);

    $this->artisan('omise:refund')
        ->expectsQuestion('Enter the charge ID to refund', 'chrg_a')
        ->expectsQuestion('Enter the amount to refund', '50')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/refunds') && $request['amount'] === '5000');
});

it('restores the executor of the SDK', function () {
    $executor = new ReflectionProperty(OmiseApiResource::class, 'httpExecutor');
    $instances = new ReflectionProperty(OmiseApiResource::class, 'instances');

    Transport::useDriver('laravel');

    expect($executor->getValue())->toBe(Transport::executor());

    Transport::useDriver('sdk');

    expect($executor->getValue())->toBeNull()
        ->and($instances->getValue())->toBe([])
        ->and(Transport::executor())->toBeInstanceOf(OmiseHttpExecutor::class);
});

it('rejects an unknown driver', function () {
    Transport::useDriver('guzzle');
})->throws(InvalidArgumentException::class);
