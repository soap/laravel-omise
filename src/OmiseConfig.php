<?php

namespace Soap\LaravelOmise;

class OmiseConfig
{
    private $canInitialize = false;

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        // Initialize only if both keys are present
        $publicKey = $this->getPublicKey();
        $secretKey = $this->getSecretKey();

        // Check that both keys are set and not empty strings
        if (! empty($publicKey) && ! empty($secretKey)) {
            $this->canInitialize = true;
        }
    }

    /**
     * Get config dynamically to support runtime changes
     */
    private function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return config('omise', []);
        }

        return config("omise.{$key}", $default);
    }

    public function canInitialize(): bool
    {
        return $this->canInitialize;
    }

    public function getUrl(): string
    {
        return $this->getConfig('api.url', 'https://api.omise.co');
    }

    public function getApiVersion(): string
    {
        return $this->getConfig('api.version', '2019-05-29');
    }

    public function isSandboxEnabled(): bool
    {
        return $this->getConfig('sandbox', true);
    }

    /**
     * Check if running in sandbox mode (alias for backward compatibility)
     */
    public function isSandbox(): bool
    {
        return $this->isSandboxEnabled();
    }

    public function getPublicKey(): string
    {
        if ($this->isSandboxEnabled()) {
            return $this->getConfig('keys.test.public', '');
        }

        return $this->getConfig('keys.live.public', '');
    }

    public function getSecretKey(): string
    {
        if ($this->isSandboxEnabled()) {
            return $this->getConfig('keys.test.secret', '');
        }

        return $this->getConfig('keys.live.secret', '');
    }

    /**
     * Get HTTP configuration
     */
    public function getHttpConfig(): array
    {
        $httpConfig = $this->getConfig('http', []);

        return ! empty($httpConfig) ? $httpConfig : [
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify_ssl' => true,
            'user_agent' => 'Laravel-Omise-Package/1.0',
        ];
    }

    /**
     * Get logging configuration
     */
    public function getLoggingConfig(): array
    {
        $loggingConfig = $this->getConfig('logging', []);

        return ! empty($loggingConfig) ? $loggingConfig : [
            'enabled' => true,
            'channel' => 'daily',
            'level' => 'info',
            'mask_sensitive' => true,
        ];
    }

    /**
     * Get rate limiting configuration
     */
    public function getRateLimitingConfig(): array
    {
        $rateLimitConfig = $this->getConfig('rate_limiting', []);

        return ! empty($rateLimitConfig) ? $rateLimitConfig : [
            'enabled' => true,
            'requests_per_minute' => 60,
            'burst_limit' => 10,
        ];
    }

    /**
     * Get error handling configuration
     */
    public function getErrorHandlingConfig(): array
    {
        $errorConfig = $this->getConfig('error_handling', []);

        return ! empty($errorConfig) ? $errorConfig : [
            'throw_exceptions' => true,
            'retry_failed_requests' => true,
            'max_retries' => 3,
            'retry_delay' => 1000,
        ];
    }

    /**
     * Get cache configuration
     */
    public function getCacheConfig(): array
    {
        $cacheConfig = $this->getConfig('cache', []);

        return ! empty($cacheConfig) ? $cacheConfig : [
            'enabled' => true,
            'store' => 'default',
            'ttl' => [
                'capabilities' => 3600,
                'account' => 1800,
            ],
            'prefix' => 'omise',
        ];
    }

    /**
     * Check if development mode is enabled
     */
    public function isDevelopmentMode(): bool
    {
        return $this->getConfig('development.debug_mode', false);
    }

    /**
     * Check if fake responses are enabled
     */
    public function isFakeResponsesEnabled(): bool
    {
        return $this->getConfig('development.fake_responses', false);
    }

    /**
     * Get package metadata
     */
    public function getPackageInfo(): array
    {
        return $this->getConfig('package', [
            'version' => '1.0.0',
            'name' => 'soap/laravel-omise',
            'source' => 'laravel-omise-package',
        ]);
    }

    /**
     * Get all core configuration
     */
    public function getAllConfig(): array
    {
        return $this->getConfig();
    }

    /**
     * Refresh configuration (useful for testing)
     */
    public function refresh(): void
    {
        $this->init();
    }
}
