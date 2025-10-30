<?php

use Soap\LaravelOmise\Omise\Capabilities;
use Soap\LaravelOmise\OmiseConfig;

beforeEach(function () {
    putenv('OMISE_TEST_PUBLIC_KEY=pkey_test_5q2qjs6ks3kehbic85t');
    putenv('OMISE_TEST_SECRET_KEY=skey_test_5q2qjs6kst7j985ncow');
    putenv('OMISE_SANDBOX_STATUS=true');
    
    config([
        'omise.test_public_key' => getenv('OMISE_TEST_PUBLIC_KEY'),
        'omise.test_secret_key' => getenv('OMISE_TEST_SECRET_KEY'),
        'omise.sandbox_status' => true,
        'omise.url' => 'https://api.omise.co',
    ]);
});

it('can create capabilities instance', function () {
    $config = new OmiseConfig();
    $capabilities = new Capabilities($config);
    
    expect($capabilities)->toBeInstanceOf(Capabilities::class);
});

it('can check if payment method is supported', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    // Mock the object with payment methods
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'payment_methods' => [
            ['name' => 'card'],
            ['name' => 'promptpay'],
            ['name' => 'truemoney'],
        ]
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    expect($capabilities->hasPaymentMethod('promptpay'))->toBeTrue();
    expect($capabilities->hasPaymentMethod('card'))->toBeTrue();
    expect($capabilities->hasPaymentMethod('nonexistent'))->toBeFalse();
});

it('can get supported currencies', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'payment_methods' => [
            ['name' => 'card', 'currencies' => ['THB', 'USD', 'EUR']],
            ['name' => 'promptpay', 'currencies' => ['THB']],
            ['name' => 'alipay', 'currencies' => ['THB', 'USD']],
        ]
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    $currencies = $capabilities->getSupportedCurrencies();
    
    expect($currencies)->toBeArray()
        ->toContain('THB')
        ->toContain('USD')
        ->toContain('EUR');
    
    expect(count(array_unique($currencies)))->toBe(count($currencies));
});

it('can get supported banks', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'banks' => ['bbl', 'kbank', 'scb', 'bay']
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    $banks = $capabilities->getSupportedBanks();
    
    expect($banks)->toBeArray()
        ->toHaveCount(4)
        ->toContain('bbl')
        ->toContain('kbank');
});

it('can get country code', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = ['country' => 'TH'];
    $objectProperty->setValue($capabilities, $mockData);
    
    expect($capabilities->getCountry())->toBe('TH');
});

it('can filter payment methods by currency', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'payment_methods' => [
            ['name' => 'card', 'currencies' => ['THB', 'USD']],
            ['name' => 'promptpay', 'currencies' => ['THB']],
            ['name' => 'paynow', 'currencies' => ['SGD']],
        ]
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    $thbMethods = $capabilities->getBackends('THB');
    expect($thbMethods)->toHaveCount(2);
    
    $sgdMethods = $capabilities->getBackends('SGD');
    expect($sgdMethods)->toHaveCount(1);
});

it('can get installment backends', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'payment_methods' => [
            ['name' => 'card', 'currencies' => ['THB']],
            ['name' => 'installment_bay', 'currencies' => ['THB'], 'installment_terms' => [3, 6, 10]],
            ['name' => 'installment_kbank', 'currencies' => ['THB'], 'installment_terms' => [3, 4, 6]],
            ['name' => 'promptpay', 'currencies' => ['THB']],
        ],
        'limits' => [
            'installment_amount' => ['min' => 200000]
        ]
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    $installments = $capabilities->getInstallmentBackends();
    expect($installments)->toHaveCount(2);
    
    $installments = $capabilities->getInstallmentBackends('THB');
    expect($installments)->toHaveCount(2);
});

it('can check if installments are supported', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    // With installments
    $mockData = [
        'payment_methods' => [
            ['name' => 'installment_bay', 'currencies' => ['THB'], 'installment_terms' => [3, 6]],
        ]
    ];
    $objectProperty->setValue($capabilities, $mockData);
    expect($capabilities->supportsInstallments())->toBeTrue();
    
    // Without installments
    $mockData = [
        'payment_methods' => [
            ['name' => 'card', 'currencies' => ['THB']],
        ]
    ];
    $objectProperty->setValue($capabilities, $mockData);
    expect($capabilities->supportsInstallments())->toBeFalse();
});

it('can get payment method by type', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'payment_methods' => [
            ['name' => 'mobile_banking_scb', 'currencies' => ['THB']],
            ['name' => 'mobile_banking_bay', 'currencies' => ['THB']],
            ['name' => 'promptpay', 'currencies' => ['THB']],
        ]
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    $mobileBanking = $capabilities->getPaymentMethodsByType('mobile_banking');
    expect($mobileBanking)->toHaveCount(2);
});

it('can convert to array', function () {
    $capabilities = new Capabilities(new OmiseConfig());
    
    $reflection = new ReflectionClass($capabilities);
    $objectProperty = $reflection->getProperty('object');
    $objectProperty->setAccessible(true);
    
    $mockData = [
        'object' => 'capability',
        'location' => '/capability',
        'country' => 'TH',
        'banks' => ['bbl', 'kbank'],
        'limits' => ['charge_amount' => ['min' => 2000]],
        'payment_methods' => [['name' => 'card']],
        'tokenization_methods' => ['googlepay'],
        'zero_interest_installments' => false,
    ];
    
    $objectProperty->setValue($capabilities, $mockData);
    
    $array = $capabilities->toArray();
    
    expect($array)->toBeArray()
        ->toHaveKey('object')
        ->toHaveKey('country')
        ->toHaveKey('banks')
        ->toHaveKey('payment_methods');
    
    expect($array['country'])->toBe('TH');
});