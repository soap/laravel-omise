<?php

// config/omise-payment.php - Payment Layer Configuration

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the payment processing layer built on top of
    | the core Omise wrapper. This handles business logic, payment methods,
    | and user experience settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Payment Settings
    |--------------------------------------------------------------------------
    |
    | Default settings that apply to all payment methods unless overridden
    |
    */
    'defaults' => [
        'currency' => env('OMISE_PAYMENT_DEFAULT_CURRENCY', 'THB'),
        'capture' => env('OMISE_PAYMENT_DEFAULT_CAPTURE', true),
        'return_uri' => env('OMISE_PAYMENT_DEFAULT_RETURN_URI', null),
        'description_prefix' => env('OMISE_PAYMENT_DESCRIPTION_PREFIX', ''),
        'metadata' => [
            'source' => 'laravel-omise-payment-layer',
            'environment' => env('APP_ENV', 'production'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods Configuration
    |--------------------------------------------------------------------------
    |
    | Configure individual payment methods and their specific settings
    |
    */
    'methods' => [
        /*
        |--------------------------------------------------------------------------
        | Online Payment Methods
        |--------------------------------------------------------------------------
        */
        'credit_card' => [
            'enabled' => env('OMISE_PAYMENT_CREDIT_CARD_ENABLED', true),
            'capture' => env('OMISE_PAYMENT_CREDIT_CARD_CAPTURE', true),
            'supports_3ds' => env('OMISE_PAYMENT_CREDIT_CARD_3DS', true),
            'currencies' => ['THB', 'USD', 'EUR', 'GBP', 'SGD', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY', 'DKK', 'HKD', 'MYR'],
            'limits' => [
                'THB' => ['min' => 20, 'max' => 200000],
                'USD' => ['min' => 1, 'max' => 5000],
                'default' => ['min' => 1, 'max' => 999999],
            ],
            'features' => [
                'partial_capture' => true,
                'void_authorization' => true,
                'delayed_capture' => true,
            ],
        ],

        'installment' => [
            'enabled' => env('OMISE_PAYMENT_INSTALLMENT_ENABLED', true),
            'currencies' => ['THB'],
            'supported_terms' => [3, 4, 6, 9, 10, 12, 18, 24, 36],
            'minimum_amounts' => [
                3 => 3000,
                4 => 4000,
                6 => 6000,
                9 => 9000,
                10 => 10000,
                12 => 12000,
                18 => 18000,
                24 => 24000,
                36 => 36000,
            ],
            'zero_interest_enabled' => env('OMISE_PAYMENT_INSTALLMENT_ZERO_INTEREST', false),
            'features' => [
                'auto_capture_only' => true,
                'immediate_processing' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Offline Payment Methods
        |--------------------------------------------------------------------------
        */
        'promptpay' => [
            'enabled' => env('OMISE_PAYMENT_PROMPTPAY_ENABLED', true),
            'currencies' => ['THB'],
            'limits' => [
                'min_amount' => env('OMISE_PAYMENT_PROMPTPAY_MIN', 20),
                'max_amount' => env('OMISE_PAYMENT_PROMPTPAY_MAX', 1000000),
                'daily_limit' => env('OMISE_PAYMENT_PROMPTPAY_DAILY_LIMIT', 2000000),
            ],
            'expiration_minutes' => env('OMISE_PAYMENT_PROMPTPAY_EXPIRATION', 15),
            'prefer_webhooks' => env('OMISE_PAYMENT_PROMPTPAY_PREFER_WEBHOOKS', true),
            'polling' => [
                'interval_seconds' => 3,
                'max_attempts' => 300,
                'backup_when_webhooks' => true,
                'backup_interval_seconds' => 10,
            ],
            'qr_code' => [
                'display_mode' => env('OMISE_PAYMENT_PROMPTPAY_QR_MODE', 'popup'), // popup, inline, modal
                'size' => env('OMISE_PAYMENT_PROMPTPAY_QR_SIZE', 'medium'), // small, medium, large
            ],
        ],

        'internet_banking' => [
            'enabled' => env('OMISE_PAYMENT_INTERNET_BANKING_ENABLED', true),
            'currencies' => ['THB'],
            'expiration_minutes' => env('OMISE_PAYMENT_INTERNET_BANKING_EXPIRATION', 30),
            'supported_banks' => [
                'scb' => [
                    'name' => 'Siam Commercial Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_SCB_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 2000000, 'daily_limit' => 5000000],
                ],
                'bbl' => [
                    'name' => 'Bangkok Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_BBL_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 2000000, 'daily_limit' => 5000000],
                ],
                'ktb' => [
                    'name' => 'Krung Thai Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_KTB_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 1000000, 'daily_limit' => 3000000],
                ],
                'kbank' => [
                    'name' => 'Kasikorn Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_KBANK_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 2000000, 'daily_limit' => 5000000],
                ],
                'bay' => [
                    'name' => 'Bank of Ayudhya (Krungsri)',
                    'enabled' => env('OMISE_PAYMENT_BANK_BAY_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 2000000, 'daily_limit' => 5000000],
                ],
                'gsb' => [
                    'name' => 'Government Savings Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_GSB_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 500000, 'daily_limit' => 1000000],
                ],
                'ttb' => [
                    'name' => 'TMBThanachart Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_TTB_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 2000000, 'daily_limit' => 5000000],
                ],
                'uob' => [
                    'name' => 'United Overseas Bank',
                    'enabled' => env('OMISE_PAYMENT_BANK_UOB_ENABLED', true),
                    'limits' => ['min' => 10, 'max' => 2000000, 'daily_limit' => 5000000],
                ],
            ],
            'polling' => [
                'interval_seconds' => 5,
                'max_attempts' => 360,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook integration with offline payment methods
    |
    */
    'webhooks' => [
        'enabled' => env('OMISE_PAYMENT_WEBHOOKS_ENABLED', false),
        'secret' => env('OMISE_PAYMENT_WEBHOOK_SECRET', ''),
        'verify_signature' => env('OMISE_PAYMENT_WEBHOOK_VERIFY_SIGNATURE', true),

        'endpoints' => [
            'promptpay' => env('OMISE_PAYMENT_WEBHOOK_PROMPTPAY', '/webhooks/omise/promptpay'),
            'internet_banking' => env('OMISE_PAYMENT_WEBHOOK_BANKING', '/webhooks/omise/banking'),
            'installment' => env('OMISE_PAYMENT_WEBHOOK_INSTALLMENT', '/webhooks/omise/installment'),
            'general' => env('OMISE_PAYMENT_WEBHOOK_GENERAL', '/webhooks/omise/general'),
        ],

        'events' => [
            'charge.complete' => env('OMISE_PAYMENT_WEBHOOK_CHARGE_COMPLETE', true),
            'charge.failed' => env('OMISE_PAYMENT_WEBHOOK_CHARGE_FAILED', true),
            'charge.expired' => env('OMISE_PAYMENT_WEBHOOK_CHARGE_EXPIRED', true),
            'source.chargeable' => env('OMISE_PAYMENT_WEBHOOK_SOURCE_CHARGEABLE', true),
            'refund.created' => env('OMISE_PAYMENT_WEBHOOK_REFUND_CREATED', true),
        ],

        'retry' => [
            'enabled' => env('OMISE_PAYMENT_WEBHOOK_RETRY_ENABLED', true),
            'max_attempts' => env('OMISE_PAYMENT_WEBHOOK_MAX_RETRIES', 3),
            'delay_seconds' => env('OMISE_PAYMENT_WEBHOOK_RETRY_DELAY', 60),
        ],

        'security' => [
            'ip_whitelist' => env('OMISE_PAYMENT_WEBHOOK_IP_WHITELIST', ''),
            'rate_limit' => env('OMISE_PAYMENT_WEBHOOK_RATE_LIMIT', 60), // per minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Payment Processors
    |--------------------------------------------------------------------------
    |
    | Register custom payment processors that extend the default functionality
    |
    */
    'custom_processors' => [
        // Example:
        // 'truemoney' => App\PaymentProcessors\TruemoneyProcessor::class,
        // 'shopeepay' => App\PaymentProcessors\ShopeepayProcessor::class,
        // 'rabbit_linepay' => App\PaymentProcessors\RabbitLinepayProcessor::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Experience Settings
    |--------------------------------------------------------------------------
    |
    | Configure user experience aspects of payment processing
    |
    */
    'user_experience' => [
        'show_processing_time' => env('OMISE_PAYMENT_SHOW_PROCESSING_TIME', true),
        'auto_redirect_on_success' => env('OMISE_PAYMENT_AUTO_REDIRECT_SUCCESS', false),
        'success_redirect_delay' => env('OMISE_PAYMENT_SUCCESS_REDIRECT_DELAY', 3), // seconds
        'error_retry_enabled' => env('OMISE_PAYMENT_ERROR_RETRY_ENABLED', true),
        'max_retry_attempts' => env('OMISE_PAYMENT_MAX_RETRY_ATTEMPTS', 3),

        'messaging' => [
            'locale' => env('OMISE_PAYMENT_LOCALE', 'en'),
            'show_detailed_errors' => env('OMISE_PAYMENT_SHOW_DETAILED_ERRORS', false),
            'custom_messages' => [
                'processing' => env('OMISE_PAYMENT_MSG_PROCESSING', 'Processing your payment...'),
                'success' => env('OMISE_PAYMENT_MSG_SUCCESS', 'Payment completed successfully!'),
                'failed' => env('OMISE_PAYMENT_MSG_FAILED', 'Payment failed. Please try again.'),
                'expired' => env('OMISE_PAYMENT_MSG_EXPIRED', 'Payment session expired. Please start again.'),
            ],
        ],

        'mobile_optimizations' => [
            'reduce_polling_frequency' => env('OMISE_PAYMENT_MOBILE_REDUCE_POLLING', true),
            'show_app_switch_hints' => env('OMISE_PAYMENT_MOBILE_APP_HINTS', true),
            'optimize_qr_size' => env('OMISE_PAYMENT_MOBILE_OPTIMIZE_QR', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics & Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure analytics and monitoring for payment processing
    |
    */
    'analytics' => [
        'enabled' => env('OMISE_PAYMENT_ANALYTICS_ENABLED', true),
        'track_conversion_rates' => env('OMISE_PAYMENT_TRACK_CONVERSION', true),
        'track_processing_times' => env('OMISE_PAYMENT_TRACK_PROCESSING_TIMES', true),
        'track_error_rates' => env('OMISE_PAYMENT_TRACK_ERROR_RATES', true),

        'providers' => [
            'google_analytics' => env('OMISE_PAYMENT_GA_ENABLED', false),
            'mixpanel' => env('OMISE_PAYMENT_MIXPANEL_ENABLED', false),
            'custom' => env('OMISE_PAYMENT_CUSTOM_ANALYTICS', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for payment processing
    |
    */
    'security' => [
        'mask_sensitive_data' => env('OMISE_PAYMENT_MASK_SENSITIVE', true),
        'log_payment_details' => env('OMISE_PAYMENT_LOG_DETAILS', false),
        'encrypt_stored_data' => env('OMISE_PAYMENT_ENCRYPT_STORED', true),

        'fraud_detection' => [
            'enabled' => env('OMISE_PAYMENT_FRAUD_DETECTION', false),
            'max_amount_threshold' => env('OMISE_PAYMENT_FRAUD_MAX_AMOUNT', 50000),
            'velocity_checks' => env('OMISE_PAYMENT_FRAUD_VELOCITY_CHECKS', true),
        ],

        'validation' => [
            'strict_amount_validation' => env('OMISE_PAYMENT_STRICT_AMOUNT_VALIDATION', true),
            'strict_currency_validation' => env('OMISE_PAYMENT_STRICT_CURRENCY_VALIDATION', true),
            'validate_return_uris' => env('OMISE_PAYMENT_VALIDATE_RETURN_URIS', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Settings that may differ between environments
    |
    */
    'environments' => [
        'development' => [
            'mock_payments' => env('OMISE_PAYMENT_MOCK_PAYMENTS', false),
            'simulate_processing_delay' => env('OMISE_PAYMENT_SIMULATE_DELAY', false),
            'force_test_responses' => env('OMISE_PAYMENT_FORCE_TEST_RESPONSES', false),
        ],

        'testing' => [
            'use_test_cards' => true,
            'mock_webhooks' => true,
            'disable_external_calls' => true,
        ],

        'staging' => [
            'mirror_production_config' => env('OMISE_PAYMENT_MIRROR_PRODUCTION', true),
            'enable_debug_logging' => env('OMISE_PAYMENT_STAGING_DEBUG', true),
        ],

        'production' => [
            'strict_validation' => true,
            'enable_monitoring' => true,
            'log_all_transactions' => true,
        ],
    ],
];
