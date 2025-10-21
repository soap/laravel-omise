<?php

namespace Soap\LaravelOmise\Http;

use Soap\LaravelOmise\OmiseConfig;

/**
 * HTTP Client Configurator for Omise-PHP library
 */
class OmiseHttpConfigurator
{
    protected $config;

    protected $httpClient;

    public function __construct(OmiseConfig $config)
    {
        $this->config = $config;
        $this->httpClient = new OmiseHttpClient($config);
    }

    /**
     * Configure all Omise objects with HTTP settings
     */
    public function configure(): void
    {
        $this->configureEnhancedObjects();
        $this->configureOriginalObjects();
    }

    /**
     * Configure enhanced Omise objects
     */
    protected function configureEnhancedObjects(): void
    {
        EnhancedOmiseCharge::setHttpClient($this->httpClient);
        EnhancedOmiseCustomer::setHttpClient($this->httpClient);
        EnhancedOmiseSource::setHttpClient($this->httpClient);
        EnhancedOmiseAccount::setHttpClient($this->httpClient);
        EnhancedOmiseBalance::setHttpClient($this->httpClient);
        EnhancedOmiseCapabilities::setHttpClient($this->httpClient);
    }

    /**
     * Configure original Omise objects using reflection/monkey patching
     */
    protected function configureOriginalObjects(): void
    {
        // This is a more advanced approach using reflection
        // to modify the original Omise classes if needed

        if (method_exists('OmiseApiResource', 'setCurlOptions')) {
            // If Omise-PHP supports setting cURL options directly
            \OmiseApiResource::setCurlOptions($this->httpClient->getCurlOptions());
        } else {
            // Use alternative approach
            $this->patchOmiseHttpDefaults();
        }
    }

    /**
     * Patch Omise HTTP defaults using ini_set for cURL
     */
    protected function patchOmiseHttpDefaults(): void
    {
        $httpConfig = $this->config->getHttpConfig();

        // Set default cURL options via ini_set where possible
        if (function_exists('curl_setopt_array')) {
            // Store options in a global variable that can be accessed
            // by a custom stream context or curl wrapper
            $GLOBALS['omise_curl_options'] = $this->httpClient->getCurlOptions();
        }

        // Set default timeout values
        if (isset($httpConfig['timeout'])) {
            ini_set('default_socket_timeout', $httpConfig['timeout']);
        }
    }

    /**
     * Get HTTP client instance
     */
    public function getHttpClient(): OmiseHttpClient
    {
        return $this->httpClient;
    }

    /**
     * Test HTTP configuration
     */
    public function testConfiguration(): array
    {
        $httpConfig = $this->config->getHttpConfig();

        return [
            'timeout' => $httpConfig['timeout'] ?? 30,
            'connect_timeout' => $httpConfig['connect_timeout'] ?? 10,
            'verify_ssl' => $httpConfig['verify_ssl'] ?? true,
            'user_agent' => $httpConfig['user_agent'] ?? 'Laravel-Omise-Package/1.0',
            'curl_version' => curl_version(),
            'ssl_support' => in_array('ssl', stream_get_transports()),
            'configured_at' => now()->toISOString(),
        ];
    }

    /**
     * Test actual HTTP connection to Omise API
     */
    public function testConnection(): array
    {
        try {
            $startTime = microtime(true);

            $response = $this->httpClient->executeCurl(
                $this->config->getUrl().'/account',
                null,
                [
                    'Authorization: Basic '.base64_encode($this->config->getPublicKey().':'),
                    'Content-Type: application/json',
                ]
            );

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2); // milliseconds

            return [
                'success' => $response['http_code'] < 400,
                'http_code' => $response['http_code'],
                'response_time_ms' => $responseTime,
                'curl_error' => $response['error'],
                'curl_error_number' => $response['error_number'],
                'tested_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tested_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Create configurator instance
     */
    public static function create(OmiseConfig $config): self
    {
        return new self($config);
    }
}
