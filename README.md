# Laravel Omise Integration with Ease

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soap/laravel-omise.svg?style=flat-square)](https://packagist.org/packages/soap/laravel-omise)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/soap/laravel-omise/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/soap/laravel-omise/actions?query=workflow%3Arun-tests+branch%3Amain)
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
