# Laravel Omise Integration with Ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan](https://github.com/soap/laravel-omise/actions/workflows/phpstan.yml/badge.svg)](https://github.com/soap/laravel-omise/actions/workflows/phpstan.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)

A comprehensive Laravel package for seamless Omise payment gateway integration with support for multiple payment methods, webhooks, and modern Laravel features.

## Features

- 🚀 **Easy Integration** - Simple API with Laravel facades and dependency injection
- 💳 **Multiple Payment Methods** - Credit cards, PromptPay, Mobile Banking, E-wallets, Installments
- 🔒 **Secure** - Built-in validation and error handling
- 🎯 **Type Safe** - Full PHPStan Level 5 compliance
- 🧪 **Well Tested** - Comprehensive test coverage with Pest
- 📊 **Rich CLI Commands** - Artisan commands for account management
- 🌍 **Multi-Currency** - Support for THB, USD, EUR, and more
- 🔄 **Webhooks Ready** - Compatible with [soap/laravel-omise-webhooks](https://github.com/soap/laravel-omise-webhooks)

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, 12.x, or 13.x
- Omise Account (sandbox or live)

## Installation

Install the package via composer:

```bash
composer require soap/laravel-omise
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="omise-config"
```

## Configuration

Add your Omise credentials to `.env`:

```env
# Omise Configuration
OMISE_SANDBOX_STATUS=true
OMISE_TEST_PUBLIC_KEY=pkey_test_xxxxx
OMISE_TEST_SECRET_KEY=skey_test_xxxxx
OMISE_LIVE_PUBLIC_KEY=pkey_xxxxx
OMISE_LIVE_SECRET_KEY=skey_xxxxx

# API Configuration
OMISE_API_VERSION=2019-05-29
```

Published configuration file (`config/omise.php`):

```php
return [
    'url' => 'https://api.omise.co',

    'live_public_key' => env('OMISE_LIVE_PUBLIC_KEY', ''),
    'live_secret_key' => env('OMISE_LIVE_SECRET_KEY', ''),

    'test_public_key' => env('OMISE_TEST_PUBLIC_KEY', ''),
    'test_secret_key' => env('OMISE_TEST_SECRET_KEY', ''),

    // Sent as the Omise-Version header, null = the API version of your account
    'api_version' => env('OMISE_API_VERSION', '2019-05-29'),

    'sandbox_status' => env('OMISE_SANDBOX_STATUS', true),

    // true = failed API calls throw OmiseRequestException instead of returning an Error
    'throw' => env('OMISE_THROW', false),

    // Default return_uri of the payments created with the Payment facade
    'payments' => [
        'return_uri' => env('OMISE_RETURN_URI'),
    ],

    'http' => [
        'driver' => env('OMISE_HTTP_DRIVER', 'sdk'),   // "sdk" or "laravel"
        'timeout' => env('OMISE_HTTP_TIMEOUT', 60),
        'connect_timeout' => env('OMISE_HTTP_CONNECT_TIMEOUT', 30),
    ],
];
```

If you published the configuration file before v1.4, add the `http` section to use the Laravel HTTP driver (see [HTTP Driver](#http-driver)).

## Quick Start

### 1. Verify Configuration

Test your API credentials:

```bash
php artisan omise:verify
```

### 2. Check Account Information

```bash
php artisan omise:account
```

### 3. View Available Payment Methods

```bash
php artisan omise:capabilities
```

## Artisan Commands

### Account Management

```bash
# Display account information
php artisan omise:account

# Get account balance
php artisan omise:balance

# Verify API credentials
php artisan omise:verify
```

### Capabilities

```bash
# View all payment capabilities
php artisan omise:capabilities

# Filter by currency
php artisan omise:capabilities --currency=THB

# Filter by payment type
php artisan omise:capabilities --type=installment

# Export as JSON
php artisan omise:capabilities --format=json
```

### Refund Management

```bash
# Refund a charge
php artisan omise:refund
```

### Payments

Try a payment method against the Omise API without writing a page for it:

```bash
# PromptPay: prints the URL of the QR code
php artisan omise:pay promptpay 20

# Card: with a token of Omise.js, or the Omise test card (test keys only)
php artisan omise:pay card 20.50 --card=tokn_test_xxxxx
php artisan omise:pay card 20.50 --test-card
php artisan omise:pay card 20.50 --test-card --authorize-only

# Mobile banking and installments
php artisan omise:pay mobile_banking 20 --bank=scb --return-uri=https://example.com/return
php artisan omise:pay installment 5000 --bank=bay --term=6 --test-card

# Look a payment up again
php artisan omise:payment-status chrg_test_xxxxx
```

Amounts are in the currency unit (baht). Both commands accept `--json`. With live keys `omise:pay` asks before it creates a charge.

When working on this package, run the commands with Testbench and put your test keys in `workbench/.env` (ignored by git, see `workbench/.env.example`):

```bash
vendor/bin/testbench omise:pay promptpay 20
```

See [Capabilities Command Documentation](docs/capabilities-command.md) for detailed usage.
## Usage

### Dependency Injection

Use dependency injection in your controllers:

```php
namespace App\Http\Controllers;

use Soap\LaravelOmise\Omise;
use Soap\LaravelOmise\Omise\Error;

class PaymentController extends Controller
{
    public function __construct(protected Omise $omise) {}

    public function create()
    {
        $publicKey = $this->omise->getPublicKey();
        $secretKey = $this->omise->getSecretKey();
        
        return view('payments.form', compact('publicKey'));
    }
}
```

### Facade Usage

The injected class, the `Soap\LaravelOmise\Facades\Omise` facade and `app('omise')` all resolve the same instance. Use the facade (`Omise::charge()`) or the helper:

```php
// Access account information
$account = app('omise')->account()->retrieve();
$account->livemode;      // Property access
$account->livemode();    // Method access
$account->api_version;   // Snake case
$account->apiVersion();  // Camel case

// Get API keys
$publicKey = app('omise')->getPublicKey();
$secretKey = app('omise')->getSecretKey();

// Check if in live mode
$isLive = app('omise')->liveMode();
```

## Core Features

### Account Management

Retrieve and manage your Omise account:

```php
// Get account information
$account = app('omise')->account()->retrieve();

if ($account instanceof Error) {
    // Handle error
    echo $account->getMessage();
} else {
    // Access account properties
    echo $account->email;
    echo $account->country;
    echo $account->currency;
}

// Update webhook URI
$account = app('omise')->account()->retrieve();
$result = $account->updateWebhookUri('https://yourdomain.com/api/omise/webhooks');

// Convert to array
$data = $account->toArray();
```

### Balance Information

Check your account balance:

```php
$balance = app('omise')->balance()->retrieve();

if (!($balance instanceof Error)) {
    echo $balance->getTotalAmount();        // Formatted amount
    echo $balance->getTransferableAmount(); // Available for transfer
    echo $balance->getReservedAmount();     // Reserved funds
    echo $balance->getOnHoldAmount();       // On-hold funds
    echo $balance->currency;                // Currency code
    echo $balance->getCreatedAt();          // Carbon instance
}
```

### Capabilities

Discover available payment methods and account capabilities:

```php
$capabilities = app('omise')->capabilities()->retrieve();

if (!($capabilities instanceof Error)) {
    // Get all payment methods
    $methods = $capabilities->getAvailablePaymentMethods();
    
    // Get installment options
    $installments = $capabilities->getInstallmentBackends('THB');
    
    // Get supported currencies
    $currencies = $capabilities->getSupportedCurrencies();
    
    // Get supported banks
    $banks = $capabilities->getSupportedBanks();
    
    // Check if zero interest installments available
    $zeroInterest = $capabilities->isZeroInterest();
    
    // Check specific payment method
    $hasPromptPay = $capabilities->hasPaymentMethod('promptpay');
    
    // Get payment method limits
    $minCharge = $capabilities->getChargeAmountMinLimit(); // 2000 (20 THB)
    $maxCharge = $capabilities->getChargeAmountMaxLimit(); // 15000000 (150,000 THB)
    
    // Export to array
    $data = $capabilities->toArray();
}
```

### Charges

Create and manage charges:

```php
// Create a charge
$charge = app('omise')->charge()->create([
    'amount' => 100000,      // 1000 THB (in minor units)
    'currency' => 'thb',
    'card' => $omiseToken,   // From Omise.js
    'capture' => true,
    'metadata' => [
        'order_id' => '12345',
        'customer_name' => 'John Doe'
    ]
]);

if ($charge instanceof Error) {
    // Handle error
    return response()->json([
        'error' => $charge->getMessage(),
        'code' => $charge->getCode()
    ], 400);
}

// Find existing charge
$charge = app('omise')->charge()->find('chrg_test_xxxxx');

// Check charge status
if ($charge->isSuccessful()) {
    // Payment successful
}

if ($charge->isPaid()) {
    // Charge is paid
}

if ($charge->isAwaitPayment()) {
    // Waiting for customer payment (e.g., PromptPay)
}

if ($charge->isAwaitCapture()) {
    // Authorized but not captured
}

if ($charge->isFailed()) {
    // Payment failed
}

// Get charge information
$amount = $charge->getAmount();        // 1000.00 (in major units)
$rawAmount = $charge->getRawAmount();  // 100000 (in minor units)
$currency = $charge->currency;          // 'thb'
$status = $charge->status;              // 'successful', 'failed', 'pending'

// Get metadata
$orderId = $charge->getMetadata('order_id');

// Check if fully refunded
if ($charge->isFullyRefunded()) {
    // Charge has been fully refunded
}

// Get refunded amount
$refundedAmount = $charge->getRefundedAmount();        // 500.00 (in major units)
$rawRefunded = $charge->getRawRefundedAmount();        // 50000 (in minor units)

// Convert to array (the attributes returned by the API)
$data = $charge->toArray();

// Validate charge object
if ($charge->isValid()) {
    // All required properties present
}

// Debug information
$debug = $charge->getDebugInfo();

// Capture an authorized charge, or reverse it
$charge->capture();
$charge->reverse();

// Expire a pending charge (e.g. a PromptPay QR that is replaced by another payment method)
$charge->expire();
app('omise')->charge()->expire('chrg_test_xxxxx');   // by id, without loading it first
```

### Sources

Create payment sources for offline payments:

```php
// Create PromptPay source
$source = app('omise')->source()->create([
    'type' => 'promptpay',
    'amount' => 100000,
    'currency' => 'thb'
]);

if (!($source instanceof Error)) {
    // Use source in charge
    $charge = app('omise')->charge()->create([
        'amount' => 100000,
        'currency' => 'thb',
        'source' => $source->id,
        'return_uri' => route('payment.callback')
    ]);
}

// Retrieve source
$source = app('omise')->source()->retrieve('src_test_xxxxx');
```

### Customers

Manage customer information:

```php
// Create customer
$customer = app('omise')->customer()->create([
    'email' => 'customer@example.com',
    'description' => 'John Doe',
    'card' => $omiseToken
]);

// Find customer
$customer = app('omise')->customer()->find('cust_test_xxxxx');

// Update customer
$customer = app('omise')->customer()->find('cust_test_xxxxx');
$result = $customer->update([
    'email' => 'newemail@example.com',
    'description' => 'Updated Name'
]);

// Update customer by id, without loading it first (one request)
$customer = app('omise')->customer()->update(['card' => $omiseToken], 'cust_test_xxxxx');

// Get customer cards
$cards = $customer->cards();
$cards = $customer->cards(['limit' => 5, 'order' => 'reverse_chronological']);

// Delete a card
$customer->deleteCard('card_test_xxxxx');
app('omise')->customer()->deleteCard('card_test_xxxxx', 'cust_test_xxxxx');
```

### Refunds

Process refunds for charges:

```php
// Full refund
$charge = app('omise')->charge()->find('chrg_test_xxxxx');
$refund = $charge->refund([
    'amount' => $charge->amount  // Full amount
]);

// Partial refund
$refund = $charge->refund([
    'amount' => 50000  // 500 THB
]);

if ($refund instanceof Error) {
    // Handle refund error
    echo $refund->getMessage();
}
```

## Payments by Method

The `Payment` facade creates the charge of a payment method and tells what the customer has to do next. It sits on top of the classes above: the same keys, events, `Omise::fake()` and error handling apply.

```php
use Soap\LaravelOmise\Facades\Payment;

// Amounts are in the smallest unit of the currency (satang), as everywhere in the Omise API
$payment = Payment::createPayment('promptpay', 100000, 'THB', [
    'description' => 'Order 1001',
    'metadata' => ['order_id' => 1001],
]);

if ($payment->isError()) {
    return back()->withErrors($payment->getMessage());   // Soap\LaravelOmise\Omise\Error
}

$payment->chargeId();       // 'chrg_test_xxxxx'
$payment->isPending();      // true until the customer pays
$payment->qrCodeUrl();      // image of the QR code to scan
$payment->expiresAt();      // Carbon instance
$payment->charge();         // the Soap\LaravelOmise\Omise\Charge
```

| Method | Details | What happens next |
|---|---|---|
| `card` (alias `credit_card`) | `card` (token of Omise.js) and/or `customer`, optional `capture` | Paid at once, or `requiresRedirect()` for 3-D Secure |
| `promptpay` | none | `qrCodeUrl()` to show, the charge stays pending until it is paid |
| `mobile_banking` | `bank` (`scb`, `kbank`, `bay`, `bbl`, `ktb`), `return_uri` | `requiresRedirect()` to the app of the bank |
| `installment` | `bank` (`bay`, `kbank`, `ktc`...), `term` (months), `card` when the bank needs one | As a card |

Every method also accepts `description`, `metadata`, `customer`, `return_uri`, `expires_at` and `ip`. Set `OMISE_RETURN_URI` (`omise.payments.return_uri`) to give every payment a default `return_uri`.

An account that requires 3-D Secure rejects a card charge without a `return_uri` (`payment_rejected`: "3d secure is requested but return_uri is not set"). Give card payments a `return_uri`, the result then `requiresRedirect()`.

### Reading the result

A `PaymentResult` is not a paid charge. Check its state before fulfilling the order:

```php
$payment = Payment::createPayment('card', 100000, 'THB', ['card' => $request->omise_token]);

if ($payment->isError()) {
    // The API call failed: $payment->getMessage(), $payment->getOmiseCode()
} elseif ($payment->isSuccessful()) {
    // Paid
} elseif ($payment->requiresRedirect()) {
    return redirect($payment->redirectUrl());      // 3-D Secure or the banking app
} elseif ($payment->isFailed()) {
    // Declined: $payment->failureCode() (e.g. 'insufficient_fund'), $payment->failureMessage()
}
```

On the return page, or while a QR code is shown, look the payment up again. For payments that complete later, rely on the `charge.complete` webhook rather than on polling:

```php
$payment = Payment::status($chargeId);

$payment->isSuccessful();
```

A payment that cannot be created (missing token, wrong currency...) returns an `Error` with the code `invalid_payment` without calling the API. `Payment::processor('card')->validate($amount, $currency, $details)` returns the same problems as an array.

### Refunds

```php
Payment::refundPayment('chrg_test_xxxxx');          // what is left of the charge
Payment::refundPayment('chrg_test_xxxxx', 50000);   // 500 THB
```

### Limits and availability

Nothing about your account is hardcoded. The processors read it from the capabilities of the account (one API call):

```php
Payment::processor('promptpay')->isAvailable();     // is the method enabled for the account?
Payment::processor('promptpay')->amountLimits();    // ['min' => 2000, 'max' => 15000000]
```

### Another account

```php
Omise::withKeys($publicKey, $secretKey)->payments()->createPayment('promptpay', 100000);
```

### Custom payment methods

Extend `AbstractPaymentProcessor` and register it, for example in a service provider:

```php
use Soap\LaravelOmise\Services\AbstractPaymentProcessor;

class TrueMoneyPaymentProcessor extends AbstractPaymentProcessor
{
    public function getPaymentMethod(): string
    {
        return 'truemoney';
    }

    protected function validateDetails(array $details): array
    {
        return blank($details['return_uri'] ?? null) ? ['A return_uri is required.'] : [];
    }

    protected function chargeParams(int $amount, string $currency, array $details): array
    {
        return array_merge(parent::chargeParams($amount, $currency, $details), [
            'source' => ['type' => 'truemoney_jumpapp'],
        ]);
    }
}

Payment::extend('truemoney', TrueMoneyPaymentProcessor::class);
```

## Payment Examples

The examples below create the same charges with the resource classes directly.

### Credit Card Payment

```php

namespace App\Services;

use Soap\LaravelOmise\Omise\Error;

class CreditCardPaymentProcessor
{
    public function createPayment(float $amount, string $currency = 'THB', array $paymentDetails = []): array
    {
        $charge = app('omise')->charge()->create([
            'amount' => $amount * 100,
            'currency' => $currency,
            'card' => $paymentDetails['token'],
            'capture' => $paymentDetails['capture'] ?? true,
            'return_uri' => $paymentDetails['return_uri'] ?? null,
            'metadata' => $paymentDetails['metadata'] ?? [],
        ]);

        if ($charge instanceof Error) {
            return [
                'success' => false,
                'code' => $charge->getCode(),
                'error' => $charge->getMessage(),
            ];
        }

        return [
            'success' => true,
            'charge_id' => $charge->id,
            'amount' => $charge->getAmount(),
            'currency' => $charge->currency,
            'status' => $charge->status,
            'paid' => $charge->isPaid(),
            'authorize_uri' => $charge->authorize_uri ?? null,
        ];
    }
}
```

### PromptPay Payment

```php
namespace App\Services;

use Soap\LaravelOmise\Omise\Error;

class PromptPayPaymentProcessor
{
    public function createPayment(float $amount, string $currency = 'THB', array $paymentDetails = []): array
    {
        // Create PromptPay source
        $source = app('omise')->source()->create([
            'type' => 'promptpay',
            'amount' => $amount * 100,
            'currency' => $currency,
        ]);

        if ($source instanceof Error) {
            return [
                'success' => false,
                'code' => $source->getCode(),
                'error' => $source->getMessage(),
            ];
        }

        // Create charge with source
        $charge = app('omise')->charge()->create([
            'amount' => $amount * 100,
            'currency' => $currency,
            'source' => $source->id,
            'return_uri' => $paymentDetails['return_uri'] ?? null,
            'metadata' => $paymentDetails['metadata'] ?? [],
        ]);

        if ($charge instanceof Error) {
            return [
                'success' => false,
                'code' => $charge->getCode(),
                'error' => $charge->getMessage(),
            ];
        }

        return [
            'success' => true,
            'charge_id' => $charge->id,
            'amount' => $charge->getAmount(),
            'currency' => $charge->currency,
            'status' => $charge->status,
            'qr_code_url' => $charge->source['scannable_code']['image']['download_uri'] ?? null,
            'expires_at' => $charge->expires_at,
        ];
    }
}
```

### Mobile Banking Payment

```php
// Create mobile banking source
$source = app('omise')->source()->create([
    'type' => 'mobile_banking_scb',  // or mobile_banking_bay, mobile_banking_kbank, etc.
    'amount' => 100000,
    'currency' => 'thb'
]);

// Create charge
$charge = app('omise')->charge()->create([
    'amount' => 100000,
    'currency' => 'thb',
    'source' => $source->id,
    'return_uri' => route('payment.callback')
]);

// Redirect customer to authorize_uri
return redirect($charge->authorize_uri);
```

### Installment Payment

```php
// Get available installment options
$capabilities = app('omise')->capabilities()->retrieve();
$installments = $capabilities->getInstallmentBackends('THB', 200000); // Min 2000 THB

// Create installment charge
$charge = app('omise')->charge()->create([
    'amount' => 500000,  // 5000 THB
    'currency' => 'thb',
    'source' => [
        'type' => 'installment_bay',  // Bank of Ayudhya
        'installment_term' => 6        // 6 months
    ],
    'card' => $omiseToken,
    'return_uri' => route('payment.callback')
]);
```

## Error Handling

All Omise operations can return an `Error` object if something goes wrong:

```php
use Soap\LaravelOmise\Omise\Error;

$charge = app('omise')->charge()->create([...]);

if ($charge instanceof Error) {
    // Get error details
    $errorCode = $charge->getCode();        // Code of the package: 'bad_request', 'not_found', 'failed_capture'...
    $omiseCode = $charge->getOmiseCode();   // Code Omise answered with: 'invalid_card', 'used_token'... or null
    $errorMessage = $charge->getMessage();  // Human-readable message
    
    // Log error
    \Log::error('Omise charge failed', [
        'code' => $errorCode,
        'message' => $errorMessage
    ]);
    
    // Return error response
    return response()->json([
        'error' => $errorMessage
    ], 400);
}

// Success - proceed with charge
```

Prefer exceptions? Chain `throw()`: it returns the object on success and throws `Soap\LaravelOmise\Exceptions\OmiseRequestException` on failure. The exception of the Omise SDK (e.g. `OmiseInvalidCardException`) is its `getPrevious()`.

```php
use Soap\LaravelOmise\Exceptions\OmiseRequestException;

try {
    $charge = app('omise')->charge()->create([...])->throw();
} catch (OmiseRequestException $e) {
    $e->getMessage();     // Message from Omise
    $e->getErrorCode();   // Same as Error::getCode()
    $e->getOmiseCode();   // Same as Error::getOmiseCode()
    $e->getPrevious();    // OmiseException of the SDK, or a connection exception
}
```

Set `OMISE_THROW=true` (`omise.throw`) to throw on every failed call without chaining `throw()`. A result that is not checked can then no longer be mistaken for a loaded object.

`getOmiseCode()` is `null` when the error did not come from the Omise API (the connection failed or timed out, the response could not be read). The outcome of the request is then unknown: retrieve the charge before treating the payment as failed.

`isError()` is available on every result as an alternative to `instanceof Error`.

## Supported Payment Methods

This package supports all Omise payment methods:

### Card Payments
- Credit/Debit Cards (Visa, MasterCard, JCB, American Express, UnionPay)
- Google Pay
- Apple Pay

### QR Payments
- PromptPay
- Alipay
- WeChat Pay
- LINE Pay (Rabbit LINE Pay)

### Mobile Banking
- SCB Mobile Banking
- Bangkok Bank Mobile Banking
- Kasikorn Bank Mobile Banking
- Krungthai Bank Mobile Banking
- Bank of Ayudhya Mobile Banking

### Digital Wallets
- TrueMoney Wallet
- ShopeePay
- GrabPay
- Touch 'n Go
- GCash
- Dana

### Installment Payments
- Kasikorn Bank (KBank)
- Bangkok Bank (BBL)
- First Choice
- Krungthai Card (KTC)
- Bank of Ayudhya (BAY)
- Siam Commercial Bank (SCB)
- TMBThanachart Bank (TTB)
- United Overseas Bank (UOB)

### Buy Now Pay Later
- Atome

### Other Methods
- Direct Debit (various banks)
- Bill Payment (Tesco Lotus)
- FPX (Malaysia)

## Webhook Integration

For handling Omise webhooks, use the companion package:

```bash
composer require soap/laravel-omise-webhooks
```

See [soap/laravel-omise-webhooks](https://github.com/soap/laravel-omise-webhooks) for full documentation.

## Best Practices

### 1. Configuration Validation

Always verify your configuration before processing payments:

```php
if (!app('omise')->validConfig()) {
    $errors = app('omise')->configErrors();   // e.g. ['Secret key is missing']
    // Handle configuration errors
}
```

### 2. Error Handling

Always check for errors before processing results:

```php
$result = app('omise')->charge()->create([...]);

if ($result instanceof Error) {
    // Handle error appropriately
    // Log, notify, return error response
}
```

### 3. Metadata Usage

Use metadata to link Omise charges to your application entities:

```php
$charge = app('omise')->charge()->create([
    'amount' => 100000,
    'currency' => 'thb',
    'card' => $token,
    'metadata' => [
        'order_id' => $order->id,
        'user_id' => $user->id,
        'environment' => app()->environment()
    ]
]);
```

### 4. Amount Handling

Remember that Omise uses minor currency units (satang for THB):

```php
// Convert from major to minor units
$baht = 1000.00;
$satang = $baht * 100;  // 100000

// Convert from minor to major units
$satang = 100000;
$baht = $satang / 100;  // 1000.00

// Or use the helper
$baht = $charge->getAmount();      // Automatically converted
$satang = $charge->getRawAmount(); // Original minor units
```

## Advanced Usage

### Multiple Omise Accounts

`withKeys()` returns an instance that uses the keys of another account, for example the one of a tenant. The configured keys are left untouched:

```php
use Soap\LaravelOmise\Facades\Omise;

$omise = Omise::withKeys($school->omise_public_key, $school->omise_secret_key);

$charge = $omise->charge()->create([...]);
$omise->liveMode();   // false for test keys (skey_test_...), true otherwise
```

### Events

| Event | Dispatched when | Properties |
|---|---|---|
| `Soap\LaravelOmise\Events\ChargeCreated` | Omise accepted a new charge (it may still await a payment or a capture) | `$charge` |
| `Soap\LaravelOmise\Events\RefundCreated` | A charge was refunded | `$charge`, `$refund` |
| `Soap\LaravelOmise\Events\RequestFailed` | An API call failed, before the error is returned or thrown | `$error` |

```php
use Illuminate\Support\Facades\Event;
use Soap\LaravelOmise\Events\RequestFailed;

Event::listen(function (RequestFailed $event) {
    Log::warning('Omise request failed', $event->error->toArray());
});
```

### Arrays and JSON

Every result implements `Arrayable` and `JsonSerializable`, so it can be returned from a controller or passed to `response()->json()`. `isset($charge->authorize_uri)` tells whether an attribute has a value.

### HTTP Driver

By default requests are sent by the curl client of `omise/omise-php` (30s connect timeout, 60s timeout). Set `OMISE_HTTP_DRIVER=laravel` to send them with the Laravel HTTP client instead:

```env
OMISE_HTTP_DRIVER=laravel
OMISE_HTTP_TIMEOUT=30
OMISE_HTTP_CONNECT_TIMEOUT=5
```

The `laravel` driver honours `omise.url` and the timeouts above, shows up in tools that observe the HTTP client (Telescope, Pulse, `Http::globalMiddleware()`), and can be faked in tests. It requires `omise/omise-php` ^3.0.

### Multi-Environment Setup

```php
// Development
OMISE_SANDBOX_STATUS=true
OMISE_TEST_PUBLIC_KEY=pkey_test_xxx
OMISE_TEST_SECRET_KEY=skey_test_xxx

// Production  
OMISE_SANDBOX_STATUS=false
OMISE_LIVE_PUBLIC_KEY=pkey_xxx
OMISE_LIVE_SECRET_KEY=skey_xxx
```

### Charge Validation

Use built-in validation methods:

```php
$charge = app('omise')->charge()->find($id);

// Check if charge has all required properties
if ($charge->isValid()) {
    // Safe to process
}

// Get debug information
$debug = $charge->getDebugInfo();
// Returns: object_loaded, object_type, has_id, has_status, etc.
```

## Testing Your Application

`Omise::fake()` switches to the Laravel HTTP driver and registers `Http::fake()` responses, so no request reaches Omise and you can assert what was sent:

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Soap\LaravelOmise\Facades\Omise;

it('charges the card', function () {
    Http::preventStrayRequests();

    Omise::fake([
        'api.omise.co/charges/' => Http::response([
            'object' => 'charge',
            'id' => 'chrg_test_1',
            'amount' => 100000,
            'currency' => 'thb',
            'status' => 'successful',
            'paid' => true,
        ]),
    ]);

    $this->post('/checkout', ['token' => 'tokn_test_1'])->assertRedirect();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request['amount'] === '100000'      // form encoded, values are strings
        && $request['card'] === 'tokn_test_1');
});
```

Responses must be Omise objects (they need an `object` key). Return `['object' => 'error', 'code' => 'invalid_card', 'message' => '...']` to simulate a failure.

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/pest

# Run specific test suite
vendor/bin/pest --testsuite=Unit

# Run with coverage
vendor/bin/pest --coverage

# Exclude integration tests (requires API keys)
vendor/bin/pest --exclude-group=integration
```

## Static Analysis

The package maintains PHPStan Level 5 compliance:

```bash
vendor/bin/phpstan analyse
```

## Troubleshooting

### Common Issues

**Issue: "Charge not found or API returned null"**
- Verify your API keys are correct
- Check if you're using the correct environment (sandbox vs live)
- Ensure the charge ID is valid

**Issue: "Invalid card" errors**
- Use Omise.js to tokenize cards on the frontend
- Never send raw card details to your server
- Test with Omise test cards in sandbox mode

**Issue: "Configuration is invalid"**
- Run `php artisan omise:verify` to check configuration
- Ensure all required environment variables are set
- Check that keys match the environment (test keys for sandbox)

### Debug Mode

Enable detailed logging in your application:

```php
\Log::debug('Omise Charge Debug', $charge->getDebugInfo());
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
