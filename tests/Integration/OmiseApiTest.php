<?php

use Soap\LaravelOmise\Omise\Error;

beforeEach(function () {
    // Load .env if not already loaded (for Orchestra Testbench)
    if (! env('OMISE_TEST_PUBLIC_KEY') && file_exists(__DIR__.'/../../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../..');
        $dotenv->load();
    }

    // Set environment variables for Omise PHP library
    $publicKey = env('OMISE_TEST_PUBLIC_KEY');
    $secretKey = env('OMISE_TEST_SECRET_KEY');

    if (empty($publicKey) || empty($secretKey)) {
        $this->markTestSkipped('Omise test keys not found in environment');
    }

    // Clear any existing app instances to ensure fresh config
    if (app()->bound('omise')) {
        app()->forgetInstance('omise');
    }

    $_ENV['OMISE_PUBLIC_KEY'] = $publicKey;
    $_ENV['OMISE_SECRET_KEY'] = $secretKey;
    putenv('OMISE_PUBLIC_KEY='.$publicKey);
    putenv('OMISE_SECRET_KEY='.$secretKey);

    // Also define constants if not already defined
    if (! defined('OMISE_PUBLIC_KEY')) {
        define('OMISE_PUBLIC_KEY', $publicKey);
    }
    if (! defined('OMISE_SECRET_KEY')) {
        define('OMISE_SECRET_KEY', $secretKey);
    }

    // Set config according to the new structure
    config([
        'omise.api.url' => env('OMISE_API_URL', 'https://api.omise.co'),
        'omise.api.version' => env('OMISE_API_VERSION', '2019-05-29'),
        'omise.keys.test.public' => $publicKey,  // Use actual key value
        'omise.keys.test.secret' => $secretKey,  // Use actual key value
        'omise.keys.live.public' => env('OMISE_LIVE_PUBLIC_KEY'),
        'omise.keys.live.secret' => env('OMISE_LIVE_SECRET_KEY'),
        'omise.sandbox' => true, // Force sandbox mode for tests
        'omise.http.timeout' => 30,
        'omise.http.connect_timeout' => 10,
        'omise.http.verify_ssl' => true,
        'omise.http.user_agent' => 'Laravel-Omise-Package/2.0',
        'omise.logging.enabled' => true,
        'omise.cache.enabled' => false, // Disable cache for tests
        'omise.development.debug_mode' => true,
    ]);

    // Clear instances to ensure fresh config
    app()->forgetInstance('omise');
    app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

    // Verify config is loaded (without stopping execution)
    $omise = app('omise');
    if (! $omise->validConfig()) {
        $this->markTestSkipped('Omise sandbox keys not configured properly');
    }
});

// Add a simple connectivity test first
it('can verify omise configuration and connectivity')
    ->group('integration')
    ->expect(function () {
        $omise = app('omise');

        // Check if config is valid
        expect($omise->validConfig())->toBeTrue('Omise configuration should be valid');

        // Check if we have the required keys
        $publicKey = $omise->getPublicKey();
        $secretKey = $omise->getSecretKey();

        expect($publicKey)->not->toBeEmpty('Public key should not be empty');
        expect($secretKey)->not->toBeEmpty('Secret key should not be empty');
        expect($publicKey)->toStartWith('pkey_test_', 'Should be using test public key');
        expect($secretKey)->toStartWith('skey_test_', 'Should be using test secret key');

        // Try to retrieve account information
        $account = $omise->account()->retrieve();

        if ($account instanceof \Soap\LaravelOmise\Omise\Error) {
            throw new \Exception('Account retrieval failed: '.$account->getMessage());
        }

        return [
            'account_id' => $account->id,
            'email' => $account->email,
            'livemode' => $account->livemode,
            'currency' => $account->currency,
        ];
    })
    ->toHaveKey('account_id')
    ->toHaveKey('email');

it('can create token and charge successfully')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {

        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'John Doe',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        if ($token instanceof \Soap\LaravelOmise\Omise\Error) {
            throw new \Exception('Token creation failed: '.$token->getMessage());
        }

        expect($token)->not->toBeInstanceOf(Error::class);
        expect($token->id)->toStartWith('tokn_');

        $charge = app('omise')->charge()->create([
            'amount' => 100000,
            'currency' => 'thb',
            'description' => 'Token-based test charge',
            'card' => $token->id,
        ]);

        if ($charge instanceof \Soap\LaravelOmise\Omise\Error) {
            throw new \Exception('Charge creation failed: '.$charge->getMessage());
        }

        return $charge;
    })
    ->not->toBeInstanceOf(Error::class)
    ->amount->toBe(100000)
    ->currency->toBe('THB')
    ->isSuccessful()->toBeTrue()
    ->isPaid()->toBeTrue()
    ->isAuthorized()->toBeTrue();

it('can create multiple charges with different tokens')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $results = [];

        // Create 3 charges with separate tokens
        for ($i = 1; $i <= 3; $i++) {
            $token = app('omise')->token()->create([
                'card' => [
                    'name' => "Test User {$i}",
                    'number' => '4242424242424242',
                    'expiration_month' => 12,
                    'expiration_year' => date('Y') + 2,
                    'security_code' => '123',
                ],
            ]);

            expect($token)->not->toBeInstanceOf(Error::class);

            $charge = app('omise')->charge()->create([
                'amount' => $i * 10000, // 100, 200, 300 THB
                'currency' => 'thb',
                'description' => "Multiple charge test #{$i}",
                'card' => $token->id,
            ]);

            expect($charge)->not->toBeInstanceOf(Error::class);
            expect($charge->isSuccessful())->toBeTrue();

            $results[] = [
                'charge_id' => $charge->id,
                'amount' => $charge->amount,
                'token_id' => $token->id,
            ];
        }

        return $results;
    })
    ->toHaveCount(3)
    ->each->toHaveKeys(['charge_id', 'amount', 'token_id']);

it('can create token and partial refund charge')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        // Create token
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Refund Test User',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        expect($token)->not->toBeInstanceOf(Error::class);

        // Create charge
        $charge = app('omise')->charge()->create([
            'amount' => 100000, // 1000 THB
            'currency' => 'thb',
            'description' => 'Partial refund test',
            'card' => $token->id,
        ]);

        expect($charge)->not->toBeInstanceOf(Error::class);
        expect($charge->isSuccessful())->toBeTrue();

        // Partial refund (50%)
        $refund = $charge->refund(['amount' => 50000]);
        expect($refund)->not->toBeInstanceOf(Error::class);

        return [
            'original_amount' => $charge->amount,
            'refund_amount' => $refund['amount'] ?? 50000, // Use array access for refund
            'charge_id' => $charge->id,
        ];
    })
    ->toHaveKeys(['original_amount', 'refund_amount', 'charge_id']);

it('can create token and full refund charge')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Full Refund Test',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        $charge = app('omise')->charge()->create([
            'amount' => 50000, // 500 THB
            'currency' => 'thb',
            'description' => 'Full refund test',
            'card' => $token->id,
        ]);

        expect($charge->isSuccessful())->toBeTrue();

        // Full refund
        $refund = $charge->refund(['amount' => 50000]);
        expect($refund)->not->toBeInstanceOf(Error::class);

        // Check status after refund
        $updatedCharge = app('omise')->charge()->find($charge->id);

        return $updatedCharge->isFullyRefunded();
    })
    ->toBeTrue();

it('handles declined card with token approach')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Declined Card Test',
                'number' => '4000000000000002', // Omise declined test card
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        // Token creation should succeed
        expect($token)->not->toBeInstanceOf(Error::class);

        $charge = app('omise')->charge()->create([
            'amount' => 100000,
            'currency' => 'thb',
            'description' => 'Declined card test',
            'card' => $token->id,
        ]);

        return $charge;
    })
    ->toSatisfy(fn ($charge) => $charge instanceof Error ||
        $charge->isFailed() ||
        $charge->status === 'failed'
    );

it('handles insufficient funds card with token')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Insufficient Funds Test',
                'number' => '4000000000000341', // Omise insufficient funds test card
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        expect($token)->not->toBeInstanceOf(Error::class);

        $charge = app('omise')->charge()->create([
            'amount' => 100000,
            'currency' => 'thb',
            'description' => 'Insufficient funds test',
            'card' => $token->id,
        ]);

        return $charge;
    })
    ->toSatisfy(fn ($charge) => $charge instanceof Error ||
        $charge->isFailed() ||
        ($charge->failure_code && str_contains($charge->failure_code, 'insufficient'))
    );

it('can create token with different card brands')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $cardTests = [
            'visa' => '4242424242424242',
            'mastercard' => '5555555555554444',
        ];

        $results = [];

        foreach ($cardTests as $brand => $cardNumber) {
            $token = app('omise')->token()->create([
                'card' => [
                    'name' => "Test {$brand}",
                    'number' => $cardNumber,
                    'expiration_month' => 12,
                    'expiration_year' => date('Y') + 2,
                    'security_code' => '123',
                ],
            ]);

            expect($token)->not->toBeInstanceOf(Error::class);

            $charge = app('omise')->charge()->create([
                'amount' => 25000,
                'currency' => 'thb',
                'description' => "Test charge with {$brand}",
                'card' => $token->id,
            ]);

            expect($charge)->not->toBeInstanceOf(Error::class);
            expect($charge->isSuccessful())->toBeTrue();

            $results[$brand] = [
                'token_id' => $token->id,
                'charge_id' => $charge->id,
                'success' => $charge->isSuccessful(),
                'card_brand' => $token->getCardBrand(),
            ];
        }

        return $results;
    })
    ->toHaveKeys(['visa', 'mastercard'])
    ->each->toHaveKeys(['token_id', 'charge_id', 'success']);

it('can retrieve charge details after token payment')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Retrieve Test User',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        $originalCharge = app('omise')->charge()->create([
            'amount' => 75000,
            'currency' => 'thb',
            'description' => 'Retrieve test charge',
            'card' => $token->id,
            'metadata' => [
                'order_id' => 'TEST_ORDER_'.time(),
                'customer_email' => 'test@example.com',
            ],
        ]);

        expect($originalCharge->isSuccessful())->toBeTrue();

        // Retrieve charge by ID
        $retrievedCharge = app('omise')->charge()->find($originalCharge->id);
        expect($retrievedCharge)->not->toBeInstanceOf(Error::class);

        return [
            'original_id' => $originalCharge->id,
            'retrieved_id' => $retrievedCharge->id,
            'amount_match' => $originalCharge->amount === $retrievedCharge->amount,
            'metadata' => $retrievedCharge->getMetadata('order_id'),
        ];
    })
    ->toHaveKeys(['original_id', 'retrieved_id', 'amount_match', 'metadata']);

it('validates token creation with invalid card data')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Invalid Card Test',
                'number' => '1234567890123456', // Invalid card number
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        return $token;
    })
    ->toBeInstanceOf(Error::class);

it('handles expired card in token creation')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Expired Card Test',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => 2020, // Expired year
                'security_code' => '123',
            ],
        ]);

        return $token;
    })
    ->toBeInstanceOf(Error::class);

it('can handle large amount charges with token')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Large Amount Test',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        $charge = app('omise')->charge()->create([
            'amount' => 5000000, // 50,000 THB
            'currency' => 'thb',
            'description' => 'Large amount test charge',
            'card' => $token->id,
        ]);

        return $charge;
    })
    ->not->toBeInstanceOf(Error::class)
    ->amount->toBe(5000000)
    ->getAmount()->toBe(50000.0) // Converted to currency unit
    ->isSuccessful()->toBeTrue();

it('can check token usage status')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Token Usage Test',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        expect($token->isUnused())->toBeTrue();

        // Use token in charge
        $charge = app('omise')->charge()->create([
            'amount' => 50000,
            'currency' => 'thb',
            'description' => 'Token usage test',
            'card' => $token->id,
        ]);

        expect($charge->isSuccessful())->toBeTrue();

        // Check token after use
        $usedToken = app('omise')->token()->find($token->id);

        return $usedToken->isUsed();
    })
    ->toBeTrue();

it('can get token card information')
    ->group('integration')
    ->skip(fn () => ! config('omise.keys.test.public'), 'Sandbox keys not configured')
    ->expect(function () {
        $token = app('omise')->token()->create([
            'card' => [
                'name' => 'Card Info Test',
                'number' => '4242424242424242',
                'expiration_month' => 12,
                'expiration_year' => date('Y') + 2,
                'security_code' => '123',
            ],
        ]);

        return [
            'has_card_info' => ! empty($token->getCard()),
            'last_4' => $token->getCardLast4(),
            'brand' => $token->getCardBrand(),
        ];
    })
    ->toHaveKeys(['has_card_info', 'last_4', 'brand']);
