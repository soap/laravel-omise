# Laravel Omise Package Development Guide

## Architecture Overview

This Laravel package provides two distinct layers for Omise payment gateway integration:

**Core Layer (`config/omise.php`)**: Infrastructure-level API wrapper providing direct access to Omise API objects (Account, Charge, Customer, etc.) with enhanced HTTP client configuration and error handling.

**Payment Layer (`config/omise-payment.php`)**: Optional business logic layer implementing the PaymentProcessorInterface pattern for different payment methods (credit_card, promptpay, installment, internet_banking).

## Key Patterns

### Service Resolution Pattern
```php
// Core API objects accessed via dependency injection
$omise = app('omise');
$account = $omise->account()->retrieve();
$charge = $omise->charge()->create([...]);

// Payment layer uses factory pattern
$processor = app(\Soap\LaravelOmise\PaymentManager::class)->getProcessor('credit_card');
```

### Configuration Structure
- **Dual Config System**: Core package uses `config/omise.php`, payment layer uses `config/omise-payment.php`
- **Environment Detection**: Automatically switches between test/live keys based on `OMISE_SANDBOX_MODE`
- **HTTP Client Integration**: Enhanced HTTP client with configurator pattern in `src/Http/` directory

### Error Handling Pattern
All API methods return either the expected object OR `\Soap\LaravelOmise\Omise\Error` instance:
```php
$result = $omise->charge()->create([...]);
if ($result instanceof Error) {
    // Handle error
    return ['error' => $result->getMessage()];
}
```

## Development Workflows

### Essential Artisan Commands
```bash
# Verify configuration and test connection
php artisan omise:verify

# Inspect account details and API connectivity  
php artisan omise:account

# Check available payment methods (payment layer)
php artisan omise:payment-methods

# Test HTTP client configuration
php artisan omise:http-test
```

### Testing Structure
- **Unit Tests**: `tests/Unit/` - Core functionality and configuration
- **Integration Tests**: `tests/Integration/` - Real API calls with test credentials
- **Test Environment**: Uses Orchestra Testbench with automatic `.env` loading

**Critical**: Integration tests require valid Omise sandbox keys in `.env`:
```bash
OMISE_TEST_PUBLIC_KEY=pkey_test_xxxxx
OMISE_TEST_SECRET_KEY=skey_test_xxxxx
```

### Package Development Tools
```bash
# Run tests
vendor/bin/pest

# Integration tests only
vendor/bin/pest --group=integration

# Code analysis
vendor/bin/phpstan analyse

# Format code
vendor/bin/pint

# Package development server
composer serve
```

## Key Files and Responsibilities

- **`src/LaravelOmiseServiceProvider.php`**: Multi-layered service registration with conditional payment layer activation
- **`src/OmiseConfig.php`**: Dynamic configuration management with environment-aware key selection
- **`src/Omise.php`**: Main facade providing access to all API objects
- **`src/Factories/PaymentProcessorFactory.php`**: Payment method processor factory
- **`src/Http/`**: Enhanced HTTP client implementation for Omise API calls
- **`tests/Integration/OmiseApiTest.php`**: Comprehensive integration tests including boat ride booking scenarios

## Payment Processor Extension

To add custom payment methods:
```php
// Register in config/omise-payment.php
'custom_processors' => [
    'truemoney' => App\PaymentProcessors\TruemoneyProcessor::class,
],

// Implement PaymentProcessorInterface
class TruemoneyProcessor implements PaymentProcessorInterface {
    public function createPayment(float $amount, string $currency, array $paymentDetails): array
    public function processPayment(array $paymentData): array  
    public function refundPayment(string $chargeId, float $amount): bool
    // ...
}
```

## Shopping Cart Integration Patterns

The codebase includes extensive test examples for e-commerce scenarios:
- Multi-item boat ride bookings with addons
- Group bookings with corporate pricing
- Discount code applications
- Rental services with hourly pricing

See `tests/Integration/OmiseApiTest.php` for complete implementation patterns.

## Debugging and Troubleshooting

1. **Configuration Issues**: Run `php artisan omise:verify` first
2. **HTTP Client Problems**: Check `php artisan omise:http-test`  
3. **Payment Method Errors**: Use `php artisan omise:payment-methods --validate=METHOD_NAME`
4. **Integration Test Failures**: Verify sandbox keys and network connectivity

The package includes comprehensive logging when `OMISE_LOGGING_ENABLED=true` and detailed error objects for all failure scenarios.