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
- Laravel 10.x, 11.x, or 12.x
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

    'live_public_key' => env('OMISE_LIVE_PUBLIC_KEY', 'pkey_test_xxx'),
    'live_secret_key' => env('OMISE_LIVE_SECRET_KEY', 'skey_test_xxx'),

    'test_public_key' => env('OMISE_TEST_PUBLIC_KEY', ''),
    'test_secret_key' => env('OMISE_TEST_SECRET_KEY', ''),

    'api_version' => env('OMISE_API_VERSION', '2019-05-29'),

    'sandbox_status' => env('OMISE_SANDBOX_STATUS', true),
];
```

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

Or use the `app('omise')` helper:

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
$refundedAmount = $charge->getRefundedAmount();

// Validate charge object
if ($charge->isValid()) {
    // All required properties present
}

// Debug information
$debug = $charge->getDebugInfo();
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

// Get customer cards
$cards = $customer->cards();
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

## Payment Examples

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
    $errorCode = $charge->getCode();        // e.g., 'invalid_card'
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
    $errors = app('omise')->config->validate();
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

### Custom HTTP Configuration

The package supports custom HTTP configurations for special network requirements. See the configuration file for HTTP timeout and SSL options.

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
