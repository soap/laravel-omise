# Laravel Omise Integration with Ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)

Make Omise payment gateway integration easier with Laravel.

## Features

- 🚀 **Easy Integration** - Simple Laravel service provider setup
- 🔐 **Secure Configuration** - Environment-based API key management
- 💳 **Multiple Payment Methods** - Credit cards, PromptPay, Internet Banking, Installments
- 🧪 **Comprehensive Testing** - Full test suite with real API integration tests
- 🎛️ **Artisan Commands** - Built-in commands for account verification and management
- 📱 **Token Management** - Secure card tokenization support
- �💰 **Refund Support** - Full and partial refund capabilities
- 🔄 **Error Handling** - Robust error handling with detailed error objects

## Table of Contents

- [Laravel Omise Integration with Ease](#laravel-omise-integration-with-ease)
  - [Features](#features)
  - [Table of Contents](#table-of-contents)
  - [Support us](#support-us)
  - [Installation](#installation)
  - [Configuration](#configuration)
  - [Usage](#usage)
    - [Quick Start](#quick-start)
    - [Basic Usage](#basic-usage)
    - [Advanced Usage](#advanced-usage)
  - [API Objects](#api-objects)
    - [Create Omise API Objects](#create-omise-api-objects)
  - [Artisan Commands](#artisan-commands)
    - [Verify Configuration](#verify-configuration)
    - [Account Information](#account-information)
    - [Account Balance](#account-balance)
    - [Capabilities](#capabilities)
- [Verification](#verification)
- [Account Management](#account-management)
- [Token Management](#token-management)
- [Source Management (Offline Payments)](#source-management-offline-payments)
- [Charge](#charge)
  - [Find a charge object created](#find-a-charge-object-created)
  - [Charge customer for some amount](#charge-customer-for-some-amount)
  - [Examples](#examples)
    - [Complete Payment Flow Examples](#complete-payment-flow-examples)
      - [Credit Card Payment Processor](#credit-card-payment-processor)
      - [PromptPay Payment Processor](#promptpay-payment-processor)
      - [Controller Implementation Example](#controller-implementation-example)
- [Refund Management](#refund-management)
  - [Testing](#testing)
    - [Integration Tests](#integration-tests)
  - [Error Handling](#error-handling)
  - [Troubleshooting](#troubleshooting)
    - [Common Issues](#common-issues)
  - [Changelog](#changelog)
  - [Contributing](#contributing)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)

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

## Configuration

After publishing the config file, update your `.env` file with your Omise API keys:

```bash
# Omise API Configuration
OMISE_TEST_PUBLIC_KEY=pkey_test_xxxxxxxxxxxxxxxxxxxxx
OMISE_TEST_SECRET_KEY=skey_test_xxxxxxxxxxxxxxxxxxxxx
OMISE_LIVE_PUBLIC_KEY=pkey_live_xxxxxxxxxxxxxxxxxxxxx
OMISE_LIVE_SECRET_KEY=skey_live_xxxxxxxxxxxxxxxxxxxxx

# Environment Settings
OMISE_SANDBOX_MODE=true  # Set to false for production
OMISE_API_VERSION=2019-05-29

# Optional HTTP Settings
OMISE_HTTP_TIMEOUT=30
OMISE_HTTP_CONNECT_TIMEOUT=10
OMISE_VERIFY_SSL=true
```

The published config file (`config/omise.php`) contains:

```php
return [
    'api' => [
        'url' => env('OMISE_API_URL', 'https://api.omise.co'),
        'version' => env('OMISE_API_VERSION', '2019-05-29'),
    ],
    
    'keys' => [
        'live' => [
            'public' => env('OMISE_LIVE_PUBLIC_KEY', ''),
            'secret' => env('OMISE_LIVE_SECRET_KEY', ''),
        ],
        'test' => [
            'public' => env('OMISE_TEST_PUBLIC_KEY', ''),
            'secret' => env('OMISE_TEST_SECRET_KEY', ''),
        ],
    ],
    
    'sandbox' => env('OMISE_SANDBOX_MODE', true),
    
    'http' => [
        'timeout' => env('OMISE_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('OMISE_HTTP_CONNECT_TIMEOUT', 10),
        'verify_ssl' => env('OMISE_VERIFY_SSL', true),
        'user_agent' => env('OMISE_USER_AGENT', 'Laravel-Omise-Package/2.0'),
    ],
];
```


## Usage

### Quick Start

First, register with Omise and add your API keys to your `.env` file. Test your configuration:

```bash
php artisan omise:verify
```

### Basic Usage

```php
// Get Omise instance
$omise = app('omise');

// Check configuration
if (!$omise->validConfig()) {
    throw new Exception('Omise not configured properly');
}

// Create a token for credit card payment
$token = $omise->token()->create([
    'card' => [
        'name' => 'John Doe',
        'number' => '4242424242424242',
        'expiration_month' => 12,
        'expiration_year' => 2025,
        'security_code' => '123',
    ],
]);

// Create a charge
$charge = $omise->charge()->create([
    'amount' => 100000, // 1000.00 THB in satang
    'currency' => 'thb',
    'description' => 'Product purchase',
    'card' => $token->id,
]);

if ($charge->isSuccessful()) {
    echo "Payment successful!";
}
```

### Advanced Usage

You have to register with Omise, then fill in the keys as in the configuration file.
Note: you just add your keys in the .env file, and then test if it is valid using artisan command.
```bash
php artisan omise:verify
```

## API Objects

### Create Omise API Objects

To create Omise API objects like Charge, Source, Customer you can use Laravel dependency injection or the app helper:

```php
// Using dependency injection
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Soap\LaravelOmise\Omise;
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

// Using app helper
$omise = app('omise');
$account = $omise->account()->retrieve();

// Access properties in different ways
$account->livemode;           // Property access
$account->livemode();         // Method access
$account->api_version;        // Snake case (as returned from Omise)
$account->apiVersion();       // CamelCase method access

// Get API keys
$omise->getPublicKey();       // Gets live or test key based on sandbox_mode
$omise->getSecretKey();       // Gets corresponding secret key
```
## Artisan Commands

The package provides several helpful Artisan commands:

### Verify Configuration
```bash
php artisan omise:verify
```
Validates your Omise configuration and tests API connectivity.

### Account Information
```bash
php artisan omise:account
```
Display detailed account information including live mode status, supported currencies, and API version.

### Account Balance
```bash
php artisan omise:balance
```
Show current account balance with optional JSON output (`--json` flag).

### Capabilities
```bash
php artisan omise:capabilities
```
List supported payment methods and features for your account.


# Verification
# Account Management

Retrieve and configure account information:

```php
// Get account information
$account = app('omise')->account()->retrieve();

if ($account instanceof \Soap\LaravelOmise\Omise\Error) {
    echo "Error: " . $account->getMessage();
} else {
    echo "Account ID: " . $account->id;
    echo "Email: " . $account->email;
    echo "Live Mode: " . ($account->livemode ? 'Yes' : 'No');
    echo "Currency: " . $account->currency;
}

// Update webhook URI
$account->updateWebhookUri('https://mydomain.com/api/omise/webhooks');
```

# Token Management

Securely handle credit card information using tokens:

```php
// Create token from card data
$token = app('omise')->token()->create([
    'card' => [
        'name' => 'John Doe',
        'number' => '4242424242424242',
        'expiration_month' => 12,
        'expiration_year' => 2025,
        'security_code' => '123',
    ],
]);

if ($token instanceof \Soap\LaravelOmise\Omise\Error) {
    echo "Token creation failed: " . $token->getMessage();
} else {
    echo "Token created: " . $token->id;
    
    // Check token status
    echo "Used: " . ($token->isUsed() ? 'Yes' : 'No');
    echo "Valid: " . ($token->isUnused() ? 'Yes' : 'No');
    
    // Get card information
    $cardBrand = $token->getCardBrand();
    $lastDigits = $token->getMaskedCardNumber();
    $isExpired = $token->isCardExpired();
}

// Retrieve existing token
$token = app('omise')->token()->find('tokn_xxxxxxxxxxxxx');

// Use token for payment
$charge = app('omise')->charge()->create([
    'amount' => 100000,
    'currency' => 'thb',
    'card' => $token->id,
]);
```
# Source Management (Offline Payments)

Handle offline payment methods like PromptPay, Internet Banking:

```php
// Create PromptPay source
$source = app('omise')->source()->create([
    'type' => 'promptpay',
    'amount' => 100000, // 1000.00 THB
    'currency' => 'thb',
]);

if ($source instanceof \Soap\LaravelOmise\Omise\Error) {
    echo "Source creation failed: " . $source->getMessage();
} else {
    // Create charge with source
    $charge = app('omise')->charge()->create([
        'amount' => 100000,
        'currency' => 'thb',
        'source' => $source->id,
        'return_uri' => 'https://yoursite.com/payment/complete',
    ]);
    
    // Get QR code for PromptPay
    if ($charge->source['type'] === 'promptpay') {
        $qrCodeUrl = $charge->source['scannable_code']['image']['download_uri'];
        echo "QR Code: " . $qrCodeUrl;
    }
    
    // For Internet Banking
    if ($charge->source['type'] === 'internet_banking_scb') {
        $bankUrl = $charge->authorize_uri;
        echo "Redirect to bank: " . $bankUrl;
    }
}

// Supported source types
$sources = [
    'promptpay',
    'internet_banking_scb',  // Siam Commercial Bank
    'internet_banking_bbl',  // Bangkok Bank
    'internet_banking_ktb',  // Krung Thai Bank
    'internet_banking_kbank', // Kasikorn Bank
    'internet_banking_bay',  // Bank of Ayudhya
];
```

Create and manage customers for recurring payments:

```php
$customer = app('omise')->customer()->create([
    'email' => 'customer@example.com',
    'description' => 'John Doe',
    'card' => $token->id, // Token from frontend
]);

// Retrieve customer
$customer = app('omise')->customer()->find('cust_xxxxxxxxxxxxx');

// Update customer
$customer->update([
    'description' => 'John Doe - Premium Customer',
]);

// List all customers
$customers = app('omise')->customer()->all();

// Charge existing customer
$charge = app('omise')->charge()->create([
    'amount' => 100000,
    'currency' => 'thb',
    'customer' => $customer->id,
]);
```

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

## Examples

### Complete Payment Flow Examples

The following examples show real-world payment processing scenarios:

#### Credit Card Payment Processor

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

#### PromptPay Payment Processor

The PromptPay processor handles offline payments by generating QR codes:

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

#### Controller Implementation Example

Here's how to integrate the payment processors in a Laravel controller:

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

# Refund Management

Handle full and partial refunds:

```php
// Full refund
$refund = $charge->refund([
    'amount' => $charge->amount, // Full amount
]);

// Partial refund
$refund = $charge->refund([
    'amount' => 50000, // 500.00 THB
]);

// Check refund status
if ($refund instanceof \Soap\LaravelOmise\Omise\Error) {
    echo "Refund failed: " . $refund->getMessage();
} else {
    echo "Refund successful: " . $refund['id'];
}

// Get refund information
$charge = app('omise')->charge()->find('chrg_xxxxxxxxxxxxx');
$refundedAmount = $charge->getRefundedAmount();
$isFullyRefunded = $charge->isFullyRefunded();
```

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/pest

# Run only unit tests
vendor/bin/pest --exclude-group=integration

# Run only integration tests (requires valid API keys)
vendor/bin/pest --group=integration

# Run tests with coverage
vendor/bin/pest --coverage
```

### Integration Tests

The package includes comprehensive integration tests that make real API calls to Omise. To run these tests:

1. Copy `.env.example` to `.env`
2. Add your Omise sandbox API keys:
   ```bash
   OMISE_TEST_PUBLIC_KEY=pkey_test_xxxxxxxxxxxxxxxxxxxxx
   OMISE_TEST_SECRET_KEY=skey_test_xxxxxxxxxxxxxxxxxxxxx
   ```
3. Run integration tests:
   ```bash
   vendor/bin/pest --group=integration
   ```

**Note**: Integration tests use real API calls and may count against your API limits. Always use sandbox keys for testing.

## Development

### Code Quality Tools

This package uses several tools to maintain code quality across different Laravel versions:

#### PHPStan Static Analysis
- **Laravel 10**: Uses PHPStan v1.x with Larastan v2.9
- **Laravel 11+**: Uses PHPStan v2.x with Larastan v3.0

```bash
# For current Laravel version
vendor/bin/phpstan analyse

# For Laravel 10 compatibility
vendor/bin/phpstan analyse --configuration=phpstan-v1.neon.dist
```

#### Running Tests
```bash
# All tests
vendor/bin/pest

# Integration tests only
vendor/bin/pest --group=integration

# Code formatting
vendor/bin/pint
```

#### GitHub Actions
The package automatically tests against:
- **PHP**: 8.3, 8.4
- **Laravel**: 10.x, 11.x, 12.x
- **PHPStan**: Appropriate versions for each Laravel version

### Contributing

When contributing, ensure:
1. Tests pass for all Laravel versions
2. PHPStan analysis is clean
3. Code follows PSR-12 standards (use `vendor/bin/pint`)

## Error Handling

The package returns `Error` objects when API calls fail:

```php
$charge = app('omise')->charge()->create([
    'amount' => 100000,
    'currency' => 'thb',
    'card' => 'invalid_token',
]);

if ($charge instanceof \Soap\LaravelOmise\Omise\Error) {
    echo "Error Code: " . $charge->getCode();
    echo "Error Message: " . $charge->getMessage();
    
    // Handle specific errors
    switch ($charge->getCode()) {
        case 'not_found':
            echo "Token not found";
            break;
        case 'bad_request':
            echo "Invalid request parameters";
            break;
        case 'failed_capture':
            echo "Failed to capture charge";
            break;
    }
} else {
    echo "Charge successful: " . $charge->id;
}
```

## Troubleshooting

### Common Issues

**Configuration Problems**
```bash
# Test your configuration
php artisan omise:verify

# Check account connectivity
php artisan omise:account
```

**Environment Issues**
- Ensure `.env` has correct API keys
- Check `OMISE_SANDBOX_MODE` setting
- Verify API keys start with `pkey_test_` or `skey_test_` for sandbox

**Integration Test Failures**
```bash
# Ensure you have valid sandbox keys
OMISE_TEST_PUBLIC_KEY=pkey_test_xxxxxxxxxxxxxxxxxxxxx
OMISE_TEST_SECRET_KEY=skey_test_xxxxxxxxxxxxxxxxxxxxx

# Run connectivity test first
vendor/bin/pest --filter="can verify omise configuration and connectivity"
```

**PHPStan/Larastan Version Conflicts**

If you encounter PHPStan or Larastan version conflicts:

```bash
# For Laravel 10 projects
composer require larastan/larastan:^2.9 phpstan/phpstan:^1.11 --dev

# For Laravel 11+ projects  
composer require larastan/larastan:^3.0 phpstan/phpstan:^2.0 --dev

# Run analysis with appropriate config
vendor/bin/phpstan analyse --configuration=phpstan-v1.neon.dist  # Laravel 10
vendor/bin/phpstan analyse                                        # Laravel 11+
```

**GitHub Actions Failing**

The package automatically handles different PHPStan/Larastan versions for different Laravel versions. If you see errors in CI:

1. Check the matrix configuration in `.github/workflows/run-tests.yml`
2. Ensure `composer.json` allows appropriate version ranges
3. Verify both `phpstan.neon.dist` and `phpstan-v1.neon.dist` exist

**Token Creation Issues**
- Use Omise.js on frontend to create tokens securely
- Never send raw card data to your server
- Always validate token before creating charges

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
