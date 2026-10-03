<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Soap\LaravelOmise\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Events\ChargeCreated;
use Soap\LaravelOmise\Events\RequestFailed;
use Soap\LaravelOmise\Exceptions\OmiseRequestException;
use Soap\LaravelOmise\Facades\Omise;
use Soap\LaravelOmise\Facades\Payment;
use Soap\LaravelOmise\Omise\Error;
use Soap\LaravelOmise\PaymentManager;
use Soap\LaravelOmise\PaymentResult;
use Soap\LaravelOmise\Services\AbstractPaymentProcessor;
use Soap\LaravelOmise\Services\CreditCardPaymentProcessor;
use Soap\LaravelOmise\Services\PromptPayPaymentProcessor;

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

function paymentCharge(array $attributes = []): array
{
    return array_merge([
        'object' => 'charge',
        'id' => 'chrg_a',
        'amount' => 100000,
        'currency' => 'thb',
        'status' => 'successful',
        'paid' => true,
        'authorized' => true,
        'authorize_uri' => null,
        'failure_code' => null,
        'failure_message' => null,
        'expires_at' => null,
        'source' => null,
    ], $attributes);
}

function promptPayCharge(array $attributes = []): array
{
    return paymentCharge(array_merge([
        'status' => 'pending',
        'paid' => false,
        'authorized' => false,
        'expires_at' => '2026-10-04T10:00:00Z',
        'source' => [
            'object' => 'source',
            'type' => 'promptpay',
            'scannable_code' => ['image' => ['download_uri' => 'https://api.omise.co/charges/chrg_a/documents/docu_a/downloads/qr']],
        ],
    ], $attributes));
}

it('charges a card token', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(paymentCharge())]);

    $result = Payment::createPayment('card', 100000, 'THB', [
        'card' => 'tokn_test_1',
        'capture' => false,
        'description' => 'Order 1',
        'metadata' => ['order_id' => 1],
        'unknown' => 'is not sent',
    ]);

    expect($result)->toBeInstanceOf(PaymentResult::class)
        ->and($result->chargeId())->toBe('chrg_a')
        ->and($result->paymentMethod())->toBe('card')
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->requiresAction())->toBeFalse()
        ->and($result->isError())->toBeFalse()
        ->and($result->throw())->toBe($result);

    Http::assertSent(fn (Request $request) => $request['amount'] === '100000'
        && $request['currency'] === 'thb'
        && $request['card'] === 'tokn_test_1'
        && $request['capture'] === 'false'
        && $request['description'] === 'Order 1'
        && $request['metadata'] === ['order_id' => '1']
        && ! isset($request['unknown'])
        && ! isset($request['source']));
});

it('tells when a card is declined', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(paymentCharge([
        'status' => 'failed',
        'paid' => false,
        'failure_code' => 'insufficient_fund',
        'failure_message' => 'insufficient funds in the account',
    ]))]);

    $result = Payment::createPayment('card', 100000, 'THB', ['card' => 'tokn_test_1']);

    expect($result)->toBeInstanceOf(PaymentResult::class)
        ->and($result->isFailed())->toBeTrue()
        ->and($result->isSuccessful())->toBeFalse()
        ->and($result->failureCode())->toBe('insufficient_fund')
        ->and($result->failureMessage())->toBe('insufficient funds in the account');
});

it('tells when a card requires a redirect', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(paymentCharge([
        'status' => 'pending',
        'paid' => false,
        'authorize_uri' => 'https://api.omise.co/payments/paym_a/authorize',
    ]))]);

    $result = Payment::createPayment('credit_card', 100000, 'THB', ['customer' => 'cust_test_1', 'return_uri' => 'https://example.com/return']);

    expect($result->requiresRedirect())->toBeTrue()
        ->and($result->requiresAction())->toBeTrue()
        ->and($result->redirectUrl())->toBe('https://api.omise.co/payments/paym_a/authorize')
        ->and($result->qrCodeUrl())->toBeNull();

    Http::assertSent(fn (Request $request) => $request['customer'] === 'cust_test_1'
        && $request['return_uri'] === 'https://example.com/return'
        && ! isset($request['card']));
});

it('creates a promptpay charge in one request', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(promptPayCharge())]);

    $result = Payment::createPayment('promptpay', 100000);

    expect($result)->toBeInstanceOf(PaymentResult::class)
        ->and($result->paymentMethod())->toBe('promptpay')
        ->and($result->isPending())->toBeTrue()
        ->and($result->requiresAction())->toBeTrue()
        ->and($result->requiresRedirect())->toBeFalse()
        ->and($result->qrCodeUrl())->toBe('https://api.omise.co/charges/chrg_a/documents/docu_a/downloads/qr')
        ->and($result->expiresAt()->toDateString())->toBe('2026-10-04')
        ->and($result->toArray())->toMatchArray([
            'charge_id' => 'chrg_a',
            'status' => 'pending',
            'payment_method' => 'promptpay',
            'amount' => 100000,
            'requires_action' => true,
        ])
        ->and(json_decode(json_encode($result), true)['qr_code_url'])->toBe($result->qrCodeUrl());

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request['source'] === ['type' => 'promptpay'] && $request['currency'] === 'thb');
});

it('creates a mobile banking charge', function () {
    config(['omise.payments.return_uri' => 'https://example.com/default-return']);

    Omise::fake(['api.omise.co/charges/' => Http::response(paymentCharge(['status' => 'pending', 'paid' => false, 'authorize_uri' => 'https://pay.omise.co/a']))]);

    $result = Payment::createPayment('mobile_banking', 100000, 'THB', ['bank' => 'SCB']);

    expect($result->requiresRedirect())->toBeTrue();

    Http::assertSent(fn (Request $request) => $request['source'] === ['type' => 'mobile_banking_scb']
        && $request['return_uri'] === 'https://example.com/default-return');
});

it('creates an installment charge', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(paymentCharge())]);

    Payment::createPayment('installment', 500000, 'THB', ['bank' => 'bay', 'term' => 6, 'card' => 'tokn_test_1']);

    Http::assertSent(fn (Request $request) => $request['source'] === ['type' => 'installment_bay', 'installment_term' => '6']
        && $request['card'] === 'tokn_test_1');
});

it('does not call the api for a payment that cannot be created', function (string $method, int $amount, string $currency, array $details, string $message) {
    Event::fake();
    Omise::fake();

    $result = Payment::createPayment($method, $amount, $currency, $details);

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->getCode())->toBe('invalid_payment')
        ->and($result->getOmiseCode())->toBeNull()
        ->and($result->getMessage())->toContain($message);

    Http::assertNothingSent();
    Event::assertDispatched(RequestFailed::class);
    Event::assertNotDispatched(ChargeCreated::class);
})->with([
    'no amount' => ['promptpay', 0, 'THB', [], 'The amount must be greater than zero.'],
    'promptpay in another currency' => ['promptpay', 100000, 'USD', [], 'The currency must be THB.'],
    'card without a token' => ['card', 100000, 'THB', [], 'A card token or a customer is required.'],
    'raw card data' => ['card', 100000, 'THB', ['card' => ['number' => '4242424242424242']], 'card data must not reach the server'],
    'mobile banking without a bank' => ['mobile_banking', 100000, 'THB', ['return_uri' => 'https://example.com'], 'A bank is required.'],
    'mobile banking without a return uri' => ['mobile_banking', 100000, 'THB', ['bank' => 'scb'], 'A return_uri is required.'],
    'installment without a term' => ['installment', 500000, 'THB', ['bank' => 'bay'], 'A term (number of months) is required.'],
]);

it('returns the error of the api', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(['object' => 'error', 'code' => 'used_token', 'message' => 'token was already used'], 400)]);

    $result = Payment::createPayment('card', 100000, 'THB', ['card' => 'tokn_test_1']);

    expect($result)->toBeInstanceOf(Error::class)
        ->and($result->getOmiseCode())->toBe('used_token');
});

it('throws when configured to', function () {
    config(['omise.throw' => true]);

    Omise::fake();

    Payment::createPayment('card', 100000);
})->throws(OmiseRequestException::class, 'A card token or a customer is required.');

it('rejects a payment method that is not registered', function () {
    Payment::createPayment('bitcoin', 100000);
})->throws(InvalidArgumentException::class, 'Payment method [bitcoin] is not supported.');

it('looks up the state of a payment', function () {
    Omise::fake(['api.omise.co/charges/chrg_a' => Http::response(promptPayCharge(['status' => 'successful', 'paid' => true]))]);

    $result = Payment::status('chrg_a');

    expect($result)->toBeInstanceOf(PaymentResult::class)
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->requiresAction())->toBeFalse()
        ->and($result->paymentMethod())->toBe('promptpay');
});

it('refunds what is left of a charge', function () {
    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(paymentCharge(['refunded_amount' => 30000])),
        'api.omise.co/charges/chrg_a/refunds' => Http::response(['object' => 'refund', 'id' => 'rfnd_a', 'amount' => 70000]),
    ]);

    $refund = Payment::refundPayment('chrg_a');

    expect($refund)->toBeInstanceOf(OmiseRefund::class);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/refunds') && $request['amount'] === '70000');
});

it('refunds a part of a charge', function () {
    Omise::fake([
        'api.omise.co/charges/chrg_a' => Http::response(paymentCharge()),
        'api.omise.co/charges/chrg_a/refunds' => Http::response(['object' => 'refund', 'id' => 'rfnd_a', 'amount' => 5000]),
    ]);

    Payment::refundPayment('chrg_a', 5000);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/refunds') && $request['amount'] === '5000');
});

it('creates payments with the keys of another account', function () {
    Omise::fake(['api.omise.co/charges/' => Http::response(promptPayCharge())]);

    Omise::withKeys('pkey_tenant', 'skey_tenant')->payments()->createPayment('promptpay', 100000);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('skey_tenant:')));
});

it('registers a custom payment processor', function () {
    $processor = new class(app('omise')) extends AbstractPaymentProcessor
    {
        public function getPaymentMethod(): string
        {
            return 'truemoney';
        }

        protected function chargeParams(int $amount, string $currency, array $details): array
        {
            return array_merge(parent::chargeParams($amount, $currency, $details), ['source' => ['type' => 'truemoney_qr']]);
        }
    };

    Omise::fake(['api.omise.co/charges/' => Http::response(paymentCharge())]);

    expect(Payment::supports('truemoney'))->toBeFalse();

    Payment::extend('truemoney', get_class($processor));

    expect(Payment::supports('truemoney'))->toBeTrue()
        ->and(Payment::getSupportedMethods())->toContain('card', 'promptpay', 'mobile_banking', 'installment', 'truemoney')
        ->and(Omise::payments()->supports('truemoney'))->toBeTrue();

    Payment::createPayment('truemoney', 100000);

    Http::assertSent(fn (Request $request) => $request['source'] === ['type' => 'truemoney_qr']);
});

it('describes a payment method', function () {
    expect(Payment::processor('promptpay'))->toBeInstanceOf(PromptPayPaymentProcessor::class)
        ->and(Payment::processor('promptpay')->isOffline())->toBeTrue()
        ->and(Payment::processor('promptpay')->getSupportedCurrencies())->toBe(['THB'])
        ->and(Payment::processor('card'))->toBeInstanceOf(CreditCardPaymentProcessor::class)
        ->and(Payment::processor('card'))->toBeInstanceOf(PaymentProcessorInterface::class)
        ->and(Payment::processor('card')->isOffline())->toBeFalse()
        ->and(Payment::processor('card')->hasRefundSupport())->toBeTrue()
        ->and(Payment::processor('card')->validate(100000, 'USD', ['card' => 'tokn_test_1']))->toBe([])
        ->and(app(PaymentManager::class))->toBe(Payment::getFacadeRoot());
});

it('reads the limits and the availability of a payment method from the capabilities', function () {
    Omise::fake(['api.omise.co/capability*' => Http::response([
        'object' => 'capability',
        'limits' => ['charge_amount' => ['min' => 2000, 'max' => 15000000], 'installment_amount' => ['min' => 200000]],
        'payment_methods' => [
            ['object' => 'payment_method', 'name' => 'card', 'currencies' => ['THB', 'USD']],
            ['object' => 'payment_method', 'name' => 'promptpay', 'currencies' => ['THB']],
            ['object' => 'payment_method', 'name' => 'installment_bay', 'currencies' => ['THB'], 'installment_terms' => [3, 6]],
        ],
    ])]);

    expect(Payment::processor('promptpay')->amountLimits())->toBe(['min' => 2000, 'max' => 15000000])
        ->and(Payment::processor('installment')->amountLimits())->toBe(['min' => 200000, 'max' => null])
        ->and(Payment::processor('promptpay')->isAvailable())->toBeTrue()
        ->and(Payment::processor('installment')->isAvailable())->toBeTrue()
        ->and(Payment::processor('mobile_banking')->isAvailable())->toBeFalse();
});
