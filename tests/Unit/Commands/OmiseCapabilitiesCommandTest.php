<?php

use Soap\LaravelOmise\Commands\OmiseCapabilitiesCommand;
use Soap\LaravelOmise\Omise\Capabilities;
use Soap\LaravelOmise\OmiseConfig;

beforeEach(function () {
    putenv('OMISE_TEST_PUBLIC_KEY=pkey_test_5q2qjs6ks3kehbic85t');
    putenv('OMISE_TEST_SECRET_KEY=skey_test_5q2qjs6kst7j985ncow');
    putenv('OMISE_SANDBOX_STATUS=true');
    
    config([
        'omise.test_public_key' => getenv('OMISE_TEST_PUBLIC_KEY'),
        'omise.test_secret_key' => getenv('OMISE_TEST_SECRET_KEY'),
        'omise.sandbox_status' => getenv('OMISE_SANDBOX_STATUS'),
        'omise.url' => 'https://api.omise.co',
    ]);
});

it('can create capabilities instance', function () {
    $config = new OmiseConfig();
    $capabilities = new Capabilities($config);
    
    expect($capabilities)->toBeInstanceOf(Capabilities::class);
});

it('has correct payment method names', function () {
    $command = new OmiseCapabilitiesCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('getPaymentMethodName');
    $method->setAccessible(true);
    
    expect($method->invoke($command, 'card'))->toBe('Credit/Debit Card');
    expect($method->invoke($command, 'promptpay'))->toBe('PromptPay');
    expect($method->invoke($command, 'truemoney'))->toBe('TrueMoney');
    expect($method->invoke($command, 'installment_bay'))->toBe('Bank of Ayudhya Installment');
});

it('categorizes payment methods correctly', function () {
    $command = new OmiseCapabilitiesCommand();
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('getPaymentMethodType');
    $method->setAccessible(true);
    
    expect($method->invoke($command, 'card'))->toBe('Card Payment');
    expect($method->invoke($command, 'mobile_banking_bay'))->toBe('Mobile Banking');
    expect($method->invoke($command, 'installment_bay'))->toBe('Installment Payment');
    expect($method->invoke($command, 'promptpay'))->toBe('QR Payment');
    expect($method->invoke($command, 'truemoney'))->toBe('TrueMoney Wallet');
    expect($method->invoke($command, 'googlepay'))->toBe('Digital Wallet');
});