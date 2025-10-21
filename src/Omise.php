<?php

namespace Soap\LaravelOmise;

use Soap\LaravelOmise\Omise\Account;
use Soap\LaravelOmise\Omise\Balance;
use Soap\LaravelOmise\Omise\Capabilities;
use Soap\LaravelOmise\Omise\Charge;
use Soap\LaravelOmise\Omise\Customer;
use Soap\LaravelOmise\Omise\Refund;
use Soap\LaravelOmise\Omise\Source;
use Soap\LaravelOmise\Omise\Token;

/**
 * Laravel wrapper for PHP-Omise library
 *
 * This class provides clean access to Omise API objects
 * using the new configuration structure (config/omise.php)
 * with HTTP client integration support.
 */
class Omise
{
    protected $config;

    public function __construct(OmiseConfig $omiseConfig)
    {
        $this->config = $omiseConfig;
    }

    /**
     * Check if Omise configuration is valid
     */
    public function validConfig(): bool
    {
        return $this->config->canInitialize();
    }

    /**
     * Check if running in live mode
     */
    public function liveMode(): bool
    {
        return ! $this->config->isSandboxEnabled();
    }

    /**
     * Check if sandbox mode is enabled
     */
    public function isSandbox(): bool
    {
        return $this->config->isSandboxEnabled();
    }

    /**
     * Get public key based on current mode
     */
    public function getPublicKey(): string
    {
        return $this->config->getPublicKey();
    }

    /**
     * Get secret key based on current mode
     */
    public function getSecretKey(): string
    {
        return $this->config->getSecretKey();
    }

    /**
     * Get Omise API URL
     */
    public function getUrl(): string
    {
        return $this->config->getUrl();
    }

    /**
     * Get API version
     */
    public function getApiVersion(): string
    {
        return $this->config->getApiVersion();
    }

    /**
     * Get Omise configuration instance
     */
    public function getConfig(): OmiseConfig
    {
        return $this->config;
    }

    /**
     * Get HTTP configuration
     */
    public function getHttpConfig(): array
    {
        return $this->config->getHttpConfig();
    }

    /**
     * Get logging configuration
     */
    public function getLoggingConfig(): array
    {
        return $this->config->getLoggingConfig();
    }

    /**
     * Get account instance
     */
    public function account(): Account
    {
        return new Account($this->config);
    }

    /**
     * Get capabilities instance
     */
    public function capabilities(): Capabilities
    {
        return new Capabilities($this->config);
    }

    /**
     * Get charge instance
     */
    public function charge(): Charge
    {
        return new Charge($this->config);
    }

    /**
     * Get customer instance
     */
    public function customer(): Customer
    {
        return new Customer($this->config);
    }

    /**
     * Get source instance
     */
    public function source(): Source
    {
        return new Source($this->config);
    }

    public function token(): Token
    {
        return new Token($this->config);
    }

    /**
     * Get balance instance
     */
    public function balance(): Balance
    {
        return new Balance($this->config);
    }

    /**
     * Get refund instance
     */
    public function refund(): Refund
    {
        return new Refund($this->config);
    }

    /**
     * Get package information
     */
    public function getPackageInfo(): array
    {
        return $this->config->getPackageInfo();
    }

    /**
     * Check if development mode is enabled
     */
    public function isDevelopmentMode(): bool
    {
        return $this->config->isDevelopmentMode();
    }

    /**
     * Check if fake responses are enabled
     */
    public function isFakeResponsesEnabled(): bool
    {
        return $this->config->isFakeResponsesEnabled();
    }

    /**
     * Get cache configuration
     */
    public function getCacheConfig(): array
    {
        return $this->config->getCacheConfig();
    }

    /**
     * Get rate limiting configuration
     */
    public function getRateLimitingConfig(): array
    {
        return $this->config->getRateLimitingConfig();
    }

    /**
     * Get error handling configuration
     */
    public function getErrorHandlingConfig(): array
    {
        return $this->config->getErrorHandlingConfig();
    }

    /**
     * Test HTTP connection to Omise API
     */
    public function testConnection(): array
    {
        if (app()->bound(\Soap\LaravelOmise\Http\OmiseHttpConfigurator::class)) {
            $configurator = app(\Soap\LaravelOmise\Http\OmiseHttpConfigurator::class);

            return $configurator->testConnection();
        }

        return [
            'success' => false,
            'error' => 'HTTP configurator not available',
            'message' => 'HTTP client integration not enabled',
        ];
    }

    /**
     * Get HTTP client information
     */
    public function getHttpClientInfo(): array
    {
        if (app()->bound(\Soap\LaravelOmise\Http\OmiseHttpClient::class)) {
            $httpClient = app(\Soap\LaravelOmise\Http\OmiseHttpClient::class);

            return [
                'enabled' => true,
                'curl_options_count' => count($httpClient->getCurlOptions()),
                'timeout' => $this->config->getHttpConfig()['timeout'] ?? null,
                'connect_timeout' => $this->config->getHttpConfig()['connect_timeout'] ?? null,
                'ssl_verify' => $this->config->getHttpConfig()['verify_ssl'] ?? null,
            ];
        }

        return [
            'enabled' => false,
            'message' => 'HTTP client not configured',
        ];
    }

    /**
     * Get system information for debugging
     */
    public function getSystemInfo(): array
    {
        return [
            'package' => $this->getPackageInfo(),
            'configuration' => [
                'valid' => $this->validConfig(),
                'mode' => $this->liveMode() ? 'live' : 'sandbox',
                'api_url' => $this->getUrl(),
                'api_version' => $this->getApiVersion(),
                'development_mode' => $this->isDevelopmentMode(),
            ],
            'http_client' => $this->getHttpClientInfo(),
            'php_info' => [
                'version' => PHP_VERSION,
                'curl_version' => function_exists('curl_version') ? curl_version()['version'] : 'Not available',
                'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Not available',
            ],
            'laravel_info' => [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
            ],
        ];
    }

    /**
     * Check compatibility with payment layer
     */
    public function isPaymentLayerAvailable(): bool
    {
        return config('omise-payment') !== null &&
               app()->bound(\Soap\LaravelOmise\PaymentManager::class);
    }

    /**
     * Get payment layer information (if available)
     */
    public function getPaymentLayerInfo(): ?array
    {
        if (! $this->isPaymentLayerAvailable()) {
            return null;
        }

        return [
            'available' => true,
            'webhooks_enabled' => config('omise-payment.webhooks.enabled', false),
            'supported_methods' => app()->bound(\Soap\LaravelOmise\PaymentManager::class)
                ? app(\Soap\LaravelOmise\PaymentManager::class)->getSupportedMethods()
                : [],
        ];
    }

    /**
     * Validate current configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $warnings = [];

        // Check core configuration
        if (! $this->validConfig()) {
            $errors[] = 'Invalid Omise configuration - missing API keys';
        }

        // Check API URL
        if (! filter_var($this->getUrl(), FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid API URL: '.$this->getUrl();
        }

        // Check HTTP configuration
        $httpConfig = $this->getHttpConfig();
        if (isset($httpConfig['timeout']) && $httpConfig['timeout'] <= 0) {
            $warnings[] = 'HTTP timeout should be greater than 0';
        }

        if (isset($httpConfig['connect_timeout']) && $httpConfig['connect_timeout'] <= 0) {
            $warnings[] = 'HTTP connect timeout should be greater than 0';
        }

        // Check development settings in production
        if (app()->environment('production')) {
            if ($this->isDevelopmentMode()) {
                $warnings[] = 'Development mode is enabled in production environment';
            }

            if ($this->isFakeResponsesEnabled()) {
                $warnings[] = 'Fake responses are enabled in production environment';
            }

            if (! ($httpConfig['verify_ssl'] ?? true)) {
                $warnings[] = 'SSL verification is disabled in production environment';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'checked_at' => now()->toISOString(),
        ];
    }

    /**
     * Get comprehensive status information
     */
    public function getStatus(): array
    {
        $validation = $this->validateConfiguration();
        $httpClientInfo = $this->getHttpClientInfo();
        $paymentLayerInfo = $this->getPaymentLayerInfo();

        return [
            'core' => [
                'configured' => $this->validConfig(),
                'mode' => $this->liveMode() ? 'live' : 'sandbox',
                'api_version' => $this->getApiVersion(),
            ],
            'http_client' => $httpClientInfo,
            'payment_layer' => $paymentLayerInfo,
            'validation' => $validation,
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Magic method to provide backward compatibility
     *
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $arguments)
    {
        // Backward compatibility for old method names
        $methodMap = [
            'isSandboxEnabled' => 'isSandbox',
            'getApiUrl' => 'getUrl',
        ];

        if (isset($methodMap[$method])) {
            return $this->{$methodMap[$method]}(...$arguments);
        }

        // If method doesn't exist, provide helpful error message
        throw new \BadMethodCallException(
            "Method '{$method}' does not exist on ".static::class.'. '.
            'Available methods: '.implode(', ', get_class_methods($this))
        );
    }
}
