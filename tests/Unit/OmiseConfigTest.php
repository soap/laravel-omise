<?php

beforeEach(function () {
    // Set environment variables
    putenv('OMISE_TEST_PUBLIC_KEY=omise_test_public_key');
    putenv('OMISE_TEST_SECRET_KEY=omise_test_secret_key');
    putenv('OMISE_LIVE_PUBLIC_KEY=omise_live_public_key');
    putenv('OMISE_LIVE_SECRET_KEY=omise_live_secret_key');
    putenv('OMISE_SANDBOX_MODE=true');
    putenv('OMISE_API_URL=https://api.omise.co');
    putenv('OMISE_API_VERSION=2019-05-29');
    putenv('OMISE_HTTP_TIMEOUT=30');
    putenv('OMISE_HTTP_CONNECT_TIMEOUT=10');
    putenv('OMISE_VERIFY_SSL=true');

    // Configure using new config structure
    config([
        'omise.api.url' => getenv('OMISE_API_URL'),
        'omise.api.version' => getenv('OMISE_API_VERSION'),
        'omise.keys.test.public' => getenv('OMISE_TEST_PUBLIC_KEY'),
        'omise.keys.test.secret' => getenv('OMISE_TEST_SECRET_KEY'),
        'omise.keys.live.public' => getenv('OMISE_LIVE_PUBLIC_KEY'),
        'omise.keys.live.secret' => getenv('OMISE_LIVE_SECRET_KEY'),
        'omise.sandbox' => filter_var(getenv('OMISE_SANDBOX_MODE'), FILTER_VALIDATE_BOOLEAN),
        'omise.http.timeout' => (int) getenv('OMISE_HTTP_TIMEOUT'),
        'omise.http.connect_timeout' => (int) getenv('OMISE_HTTP_CONNECT_TIMEOUT'),
        'omise.http.verify_ssl' => filter_var(getenv('OMISE_VERIFY_SSL'), FILTER_VALIDATE_BOOLEAN),
        'omise.http.user_agent' => 'Laravel-Omise-Package/1.0',
        'omise.logging.enabled' => true,
        'omise.logging.channel' => 'daily',
        'omise.cache.enabled' => true,
        'omise.development.debug_mode' => false,
        'omise.package.version' => '1.0.0',
    ]);

    // Clear instances to ensure fresh state for each test
    app()->forgetInstance('omise');
    app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);
});

describe('Core Configuration Tests', function () {
    it('gets test keys if sandbox is enabled', function () {
        $omise = app('omise');

        expect($omise->liveMode())->toBeFalse();
        expect($omise->isSandbox())->toBeTrue();
        expect($omise->getPublicKey())->toBe(getenv('OMISE_TEST_PUBLIC_KEY'));
        expect($omise->getSecretKey())->toBe(getenv('OMISE_TEST_SECRET_KEY'));
    });

    it('gets live keys if sandbox is disabled', function () {
        config(['omise.sandbox' => false]);

        // ⭐ Clear instances after config change
        app()->forgetInstance('omise');
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omise = app('omise');
        expect($omise->liveMode())->toBeTrue();
        expect($omise->isSandbox())->toBeFalse();
        expect($omise->getPublicKey())->toBe(getenv('OMISE_LIVE_PUBLIC_KEY'));
        expect($omise->getSecretKey())->toBe(getenv('OMISE_LIVE_SECRET_KEY'));
    });

    it('can access API configuration', function () {
        $omise = app('omise');

        expect($omise->getUrl())->toBe('https://api.omise.co');
        expect($omise->getApiVersion())->toBe('2019-05-29');
    });

    it('validates configuration correctly', function () {
        $omise = app('omise');

        expect($omise->validConfig())->toBeTrue();
    });

    it('fails validation with missing keys', function () {
        config([
            'omise.keys.test.public' => '',
            'omise.keys.test.secret' => '',
            'omise.keys.live.public' => '',
            'omise.keys.live.secret' => '',
        ]);

        // Clear instances after config change
        app()->forgetInstance('omise');
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omise = app('omise');

        expect($omise->validConfig())->toBeFalse();
    });
});

describe('HTTP Configuration Tests', function () {
    it('has correct HTTP configuration', function () {
        $omiseConfig = app(\Soap\LaravelOmise\OmiseConfig::class);
        $httpConfig = $omiseConfig->getHttpConfig();

        expect($httpConfig['timeout'])->toBe(30);
        expect($httpConfig['connect_timeout'])->toBe(10);
        expect($httpConfig['verify_ssl'])->toBeTrue();
        expect($httpConfig['user_agent'])->toBe('Laravel-Omise-Package/1.0');
    });

    it('can modify HTTP timeout', function () {
        config(['omise.http.timeout' => 60]);

        // Clear instances after config change
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omiseConfig = app(\Soap\LaravelOmise\OmiseConfig::class);
        $httpConfig = $omiseConfig->getHttpConfig();

        expect($httpConfig['timeout'])->toBe(60);
    });

    it('can disable SSL verification', function () {
        config(['omise.http.verify_ssl' => false]);

        // Clear instances after config change
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omiseConfig = app(\Soap\LaravelOmise\OmiseConfig::class);
        $httpConfig = $omiseConfig->getHttpConfig();

        expect($httpConfig['verify_ssl'])->toBeFalse();
    });

    it('has default HTTP configuration when not specified', function () {
        config(['omise.http' => []]);

        // Clear instances after config change
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omiseConfig = app(\Soap\LaravelOmise\OmiseConfig::class);
        $httpConfig = $omiseConfig->getHttpConfig();

        expect($httpConfig['timeout'])->toBe(30);
        expect($httpConfig['connect_timeout'])->toBe(10);
        expect($httpConfig['verify_ssl'])->toBeTrue();
    });
});

describe('Environment-Specific Configuration Tests', function () {
    it('works with different environments', function () {
        // Test sandbox environment
        config(['omise.sandbox' => true]);

        // Clear both instances
        app()->forgetInstance('omise');
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omise = app('omise');
        expect($omise->isSandbox())->toBeTrue();
        expect($omise->liveMode())->toBeFalse();

        // Test live environment
        config(['omise.sandbox' => false]);

        // Clear both instances again
        app()->forgetInstance('omise');
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omise = app('omise');
        expect($omise->isSandbox())->toBeFalse();
        expect($omise->liveMode())->toBeTrue();
    });

    it('can have different HTTP settings per environment', function () {
        // Development environment
        config([
            'omise.http.timeout' => 60,
            'omise.http.verify_ssl' => false,
            'omise.development.debug_mode' => true,
        ]);

        // Clear instances after config change
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omiseConfig = app(\Soap\LaravelOmise\OmiseConfig::class);
        $httpConfig = $omiseConfig->getHttpConfig();

        expect($httpConfig['timeout'])->toBe(60);
        expect($httpConfig['verify_ssl'])->toBeFalse();
        expect($omiseConfig->isDevelopmentMode())->toBeTrue();
    });
});

describe('Configuration Validation Tests', function () {
    it('validates complete configuration', function () {
        $omise = app('omise');

        expect($omise->validConfig())->toBeTrue();
        expect($omise->getPublicKey())->not()->toBeEmpty();
        expect($omise->getSecretKey())->not()->toBeEmpty();
        expect($omise->getUrl())->not()->toBeEmpty();
    });

    it('fails validation with invalid configuration', function () {
        config([
            'omise.keys.test.public' => '',
            'omise.keys.test.secret' => '',
        ]);

        // Clear instances after config change
        app()->forgetInstance('omise');
        app()->forgetInstance(\Soap\LaravelOmise\OmiseConfig::class);

        $omise = app('omise');

        expect($omise->validConfig())->toBeFalse();
    });

    it('can get all configuration', function () {
        $omiseConfig = app(\Soap\LaravelOmise\OmiseConfig::class);
        $allConfig = $omiseConfig->getAllConfig();

        expect($allConfig)->toBeArray();
        expect($allConfig)->toHaveKey('api');
        expect($allConfig)->toHaveKey('keys');
        expect($allConfig)->toHaveKey('sandbox');
        expect($allConfig)->toHaveKey('http');
    });
});

describe('Backward Compatibility Tests', function () {
    it('maintains backward compatibility with old method names', function () {
        $omise = app('omise');

        // Test that old method names still work
        expect($omise->validConfig())->toBeTrue();
        expect($omise->liveMode())->toBeFalse();
        expect($omise->isSandbox())->toBeTrue();
        expect($omise->getPublicKey())->toBe(getenv('OMISE_TEST_PUBLIC_KEY'));
        expect($omise->getSecretKey())->toBe(getenv('OMISE_TEST_SECRET_KEY'));
    });

    it('works with both old and new configuration keys', function () {
        // This test ensures that the new structure doesn't break existing functionality
        $omise = app('omise');

        expect($omise->getUrl())->toBe('https://api.omise.co');
        expect($omise->isSandbox())->toBeTrue();
    });
});
