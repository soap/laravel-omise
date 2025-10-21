<?php

// config/omise.php - Core Omise API Configuration

return [
    /*
    |--------------------------------------------------------------------------
    | Omise API Configuration
    |--------------------------------------------------------------------------
    |
    | Core configuration for Omise API integration.
    | This handles infrastructure-level settings like API keys, URLs, and
    | basic authentication parameters.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'api' => [
        'url' => env('OMISE_API_URL', 'https://api.omise.co'),
        'version' => env('OMISE_API_VERSION', '2019-05-29'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Keys
    |--------------------------------------------------------------------------
    |
    | Your Omise public and secret keys. These are provided by Omise
    | and should be kept secure.
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    |
    | Determine which environment you're running in.
    | Set to true for sandbox/test mode, false for live mode.
    |
    */
    'sandbox' => env('OMISE_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Request Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP client configuration for API requests
    |
    */
    'http' => [
        'timeout' => env('OMISE_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('OMISE_HTTP_CONNECT_TIMEOUT', 10),
        'verify_ssl' => env('OMISE_VERIFY_SSL', true),
        'user_agent' => env('OMISE_USER_AGENT', 'Laravel-Omise-Package/1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for Omise API interactions
    |
    */
    'logging' => [
        'enabled' => env('OMISE_LOGGING_ENABLED', true),
        'channel' => env('OMISE_LOG_CHANNEL', 'daily'),
        'level' => env('OMISE_LOG_LEVEL', 'info'),
        'mask_sensitive' => env('OMISE_MASK_SENSITIVE_DATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API requests
    |
    */
    'rate_limiting' => [
        'enabled' => env('OMISE_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('OMISE_RATE_LIMIT_RPM', 60),
        'burst_limit' => env('OMISE_RATE_BURST_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how errors should be handled
    |
    */
    'error_handling' => [
        'throw_exceptions' => env('OMISE_THROW_EXCEPTIONS', true),
        'retry_failed_requests' => env('OMISE_RETRY_FAILED_REQUESTS', true),
        'max_retries' => env('OMISE_MAX_RETRIES', 3),
        'retry_delay' => env('OMISE_RETRY_DELAY_MS', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for certain API responses (like capabilities)
    |
    */
    'cache' => [
        'enabled' => env('OMISE_CACHE_ENABLED', true),
        'store' => env('OMISE_CACHE_STORE', 'default'),
        'ttl' => [
            'capabilities' => env('OMISE_CACHE_CAPABILITIES_TTL', 3600), // 1 hour
            'account' => env('OMISE_CACHE_ACCOUNT_TTL', 1800), // 30 minutes
        ],
        'prefix' => env('OMISE_CACHE_PREFIX', 'omise'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Configuration for development and testing environments
    |
    */
    'development' => [
        'fake_responses' => env('OMISE_FAKE_RESPONSES', false),
        'debug_mode' => env('OMISE_DEBUG_MODE', false),
        'mock_external_calls' => env('OMISE_MOCK_EXTERNAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Metadata
    |--------------------------------------------------------------------------
    |
    | Internal package information
    |
    */
    'package' => [
        'version' => '2.0.0',
        'name' => 'soap/laravel-omise',
        'source' => 'laravel-omise-package',
    ],
];
