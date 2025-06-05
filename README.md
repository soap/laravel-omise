# Laravel Omise Integration with Ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan](https://github.com/soap/laravel-omise/actions/workflows/phpstan.yml/badge.svg)](https://github.com/soap/laravel-omise/actions/workflows/phpstan.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)

Make Omise payment gateway integration easier with Laravel.

## Support us



## Installation

You can install the package via composer:

```bash
composer require soap/laravel-omise
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="omise-config"
```

This is the contents of the published config file:

```php
return [
    'url' => 'https://api.omise.co',

    'live_public_key' => env('OMISE_LIVE_PUBLIC_KEY', 'pkey_test_xxx'),
    'live_secret_key' => env('OMISE_LIVE_SECRET_KEY', 'skey_test_xxx'),

    'test_public_key' => env('OMISE_TEST_PUBLIC_KEY', ''),
    'test_secret_key' => env('OMISE_TEST_SECRET_KEY', ''),

    'api_version' => env('OMISE_API_VERSION', '2019-05-29'),

    'sanbox_status' => env('OMISE_SANDBOX_STATUS', true),
];
```


## Usage

You have to register with Omise, then fill in the keys as in the configuration file.
Note: you just add your keys in the .env file, and then test if it is valid using artisan command.
```php
php artisan omise:verify

```
## Create Omise API Objects
To create Omise API objects like Charge, Source, Customer you can use Laravel independency injection (Soap\LaravelOmise\Omise) or use app('omise') and the access them like this:
```
$account = app('omise')->account()->retrieve(); // method here
$account->livemode; // access using property access
$account->livemode(); // access using method access

$account->api_version; // snake case as return from omise
$account->apiVersion(); // camelCase if use method to access

// get public key or secret key

$omise->getPublicKey(); // to get live public key or test one depends on 'sandbox_status'
$omise->getPrivateKey();
```
Here is the example usage from Controller:
```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Soap\LaravelOmise\Omise;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Error;

class PaymentController extends Controller
{
    public function __construct(protected Omise $omise) {}

    public function create()
    {
        $publicKey = $this->omise->getPublicKey();

        return view('payments.form', compact('publicKey'));
    }
}
```
## Artisan Commands

Display status of account

```php
php artisan omise:account
```
Get account balance

```php
php artisan omise:balance
```


# Verification
To validate your configuration provided in .env use the following code. Or you can use artisan command omise:verify.

```php
app('omise')->validConfig();
```
# Account
You can use account to retrieve account information from Omise, or configure some configuration parameter. For now I just add updateWebhookUri($uri).

```php
$account = app('omise')->account()->retrieve();

$account->updateWebhookUri('https::mydomain.com/api/omise/webhooks');
```
# Customer

# Charge
## Find a charge object created
To find charge transaaction using id (string provided by Omise), using find() method of charge object. You can using an id provided by a webhook called from Omise to confirm for a payment. If you want to use webhook, please visit my package; [soap/laravel-omise-webhooks](https://github.com/soap/laravel-omise-webhooks)
```php
$charge = app('omise')->charge()->find($id);
$charge->isPaid(); // is it paid?
$charge->isAwaitCapture(); // is it waiting for capture
$charge->isFailed(); // status == failed
$charge->getAmount(); // get unit amount in the based currency e.g. 100 THB
$charge->getRawAmount(); // get minor amount in based currency e.g. 10000 Satang (THB)
$charge->getMetadata('booking_id'); // get metadata you provide when create a charge
```
## Charge customer for some amount
To use a charge object to make a payment, you have to consult Omise workflow for each supported type of payment. To create a credit card payment, on frontend use Javascript to get charge token first. Then you can call charge->create() to make a corresponding payment.

Following code is what I used in one of my project supports Promptpay and Credit card payment. The first one, is credit card payment.
```php

namespace App\Services;

use App\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Omise\Error;

class CreditCardPaymentProcessor implements PaymentProcessorInterface
{
    /**
     * Use token to authorize payment
     */
    public function createPayment(float $amounnt, string $currency = 'THB', array $paymentDetails = []): array
    {
        $charge = app('omise')->charge()->create([
            'amount' => $amounnt * 100,
            'currency' => $currency,
            'card' => $paymentDetails['token'],
            'capture' => $paymentDetails['capture'] ?? true,
            'webhook_endpoints' => $paymentDetails['webhook_endpoints'] ?? null,
            'metadata' => $paymentDetails['metadata'] ?? [],
        ]);

        if ($charge instanceof Error) {
            return [
                'code' => $charge->getCode(),
                'error' => $charge->getMessage(),
            ];
        }

        return [
            'charge_id' => $charge->id,
            'amount' => $charge->amount / 100,
            'currency' => $charge->currency,
            'status' => $charge->status,
            'paid' => $charge->paid,
            'paid_at' => $charge->paid_at,
            'charge' => $charge,
        ];
    }

    /**
     * Process payment using charge id
     */
    public function processPayment(array $paymentData): array
    {
        return [];
    }

    /**
     * Refund payment using charge id
     */
    public function refundPayment(string $chargeId, float $amount): bool
    {
        return true;
    }

    public function hasRefundSupport(): bool
    {
        return true;
    }

    public function isOffline(): bool
    {
        return false;
    }
}
```
The second one is Promptpay, an offline payment. Omise create QRcode for us, then we display it for customer to scan to make a payment.
```php
<?php

namespace App\Services;

use App\Contracts\PaymentProcessorInterface;
use Soap\LaravelOmise\Omise\Error;

class PromptPayPaymentProcessor implements PaymentProcessorInterface
{
    public function createPayment(float $amount, string $currency = 'THB', array $paymentDetails = []): array
    {
        $source = app('omise')->source()->create([
            'type' => 'promptpay',
            'amount' => $amount * 100,
            'currency' => $currency,

        ]);

        if ($source instanceof Error) {
            return [
                'code' => $source->getCode(),
                'error' => $source->getMessage(),
            ];
        }

        $charge = app('omise')->charge()->create([
            'amount' => $amount * 100,
            'currency' => $currency,
            'source' => $source->id,
            'exprires_at' => $paymentDetails['expires_at'] ?? null,
            'webhook_endpoints' => $paymentDetails['webhook_endpoints'] ?? null,
            'metadata' => isset($paymentDetails['metadata']) ? $paymentDetails['metadata'] : [],
        ]);

        if ($charge instanceof Error) {
            return [
                'code' => $charge->getCode(),
                'error' => $charge->getMessage(),
            ];
        }

        return [
            'charge_id' => $charge->id,
            'amount' => $charge->amount / 100,
            'currency' => $charge->currency,
            'status' => $charge->status,
            'qr_image' => $charge->source['scannable_code']['image']['download_uri'],
            'expires_at' => $charge->expires_at,
        ];
    }

    public function processPayment(array $paymentData): array
    {
        return [];
    }

    public function refundPayment(string $chargeId, float $amount): bool
    {
        return false;
    }

    public function hasRefundSupport(): bool
    {
        return false;
    }

    public function isOffline(): bool
    {
        return true;
    }
}
```
And this some part showing how to use it in controller.
```php
        $paymentProcessor = $this->paymentProcessorFactory->make($payment_method);

        $result = $paymentProcessor->createPayment($bookingAmount, $bookingCurrency, [
            'return_uri' => route('payment.step5', ['booking' => $booking->id]),
            'capture' => true,
            'token' => $request->omiseToken ?? null,
            'metadata' => ['booking_id' => $booking->id],
        ]);
        if ($paymentProcessor->isOffline()) {
            $payment = Payment::create([
                'amount' => $bookingAmount,
                'payment_status' => 'pending',
                'payment_gateway' => 'omise',
                'payment_details' => $result,
                'payment_method' => $payment_method,
                'currency' => $bookingCurrency,
                'booking_id' => $booking->id,
            ]);
            $booking->update([
                'status' => 'pending', // or confirmed
            ]);
            BookingCreated::dispatch($booking);

            ShoppingCart::destroy();

            return redirect(route('payment.step4', ['booking' => $booking->id]));
        } elseif ($result['status'] === 'successful' && $result['paid']) {
            $payment = Payment::create([
                'amount' => $bookingAmount,
                'payment_status' => 'paid',
                'payment_gateway' => 'omise',
                'payment_details' => $result,
                'payment_method' => $payment_method,
                'currency' => $bookingCurrency,
                'booking_id' => $booking->id,
            ]);

            $booking->update([
                'status' => 'confirmed',
                'workflow_state' => 'confirmed',
            ]);

            BookingCreated::dispatch($booking);
            BookingConfirmed::dispatch($booking);

            ShoppingCart::destroy();

            return redirect(route('payment.step5', ['booking' => $booking->id]));
        } else {
            $payment = Payment::create([
                'amount' => $bookingAmount,
                'payment_status' => 'failed',
                'payment_gateway' => 'omise',
                'payment_details' => $result,
                'payment_method' => $payment_method,
                'currency' => $bookingCurrency,
                'booking_id' => $booking->id,
            ]);
            BookingCreated::dispatch($booking);
            ShoppingCart::destroy();

            // Handle failed payment
            return redirect(route('payment.step4', ['booking' => $booking->id]));
        }
```

# Refund

## Testing

```bash
vendor\bin\pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Prasit Gebsaap](https://github.com/soap)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
